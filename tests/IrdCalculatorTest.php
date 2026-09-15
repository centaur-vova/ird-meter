<?php

declare(strict_types=1);

namespace CentaurVova\IrdMeter\Tests;

use CentaurVova\IrdMeter\IrdCalculator;
use PHPUnit\Framework\TestCase;

final class IrdCalculatorTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/ird-meter-' . uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testEmptyDirectory(): void
    {
        $calculator = new IrdCalculator();
        $result = $calculator->calculate($this->tmpDir);

        self::assertSame(0, $result->ifCount);
        self::assertSame(0, $result->totalLines);
        self::assertSame(0, $result->files);
        self::assertSame(0.0, $result->density);
    }

    public function testFileWithIfStatements(): void
    {
        $this->writeFile('test.php', <<<'PHP'
<?php
if (true) {
    echo 'a';
}
if (false) {
    echo 'b';
}
PHP);

        $calculator = new IrdCalculator();
        $result = $calculator->calculate($this->tmpDir);

        self::assertSame(2, $result->ifCount);
        self::assertSame(1, $result->files);
    }

    public function testCommentsAreIgnoredByParser(): void
    {
        $this->writeFile('test.php', <<<'PHP'
<?php
// if (true) — this is a comment
/* if (false) — this is a comment */
$str = 'if (true) — this is a string';
if (true) {
    echo 'real if';
}
PHP);

        $calculator = new IrdCalculator();
        $result = $calculator->calculate($this->tmpDir);

        self::assertSame(1, $result->ifCount, 'Only one real if should be counted');
    }

    public function testIgnoreCommentsReducesTotalLines(): void
    {
        $this->writeFile('test.php', <<<'PHP'
<?php
// comment line 1
// comment line 2
// comment line 3
if (true) {
    echo 'a';
}
PHP);

        $withComments = (new IrdCalculator())->calculate($this->tmpDir);
        $withoutComments = (new IrdCalculator(ignoreComments: true))->calculate($this->tmpDir);

        self::assertGreaterThan(
            $withoutComments->totalLines,
            $withComments->totalLines,
            'With comments should have more lines',
        );
        self::assertSame(1, $withoutComments->ifCount);
    }

    public function testExcludeDirectories(): void
    {
        $this->writeFile('src/test.php', <<<'PHP'
<?php
if (true) { echo 'a'; }
PHP);

        $this->writeFile('vendor/test.php', <<<'PHP'
<?php
if (true) { echo 'b'; }
if (true) { echo 'c'; }
PHP);

        $calculator = new IrdCalculator(exclude: ['vendor']);
        $result = $calculator->calculate($this->tmpDir);

        self::assertSame(1, $result->ifCount, 'Vendor should be excluded');
    }

    public function testIncludeElseif(): void
    {
        $this->writeFile('test.php', <<<'PHP'
<?php
if (true) {
    echo 'a';
} elseif (false) {
    echo 'b';
}
PHP);

        $withoutElseif = (new IrdCalculator())->calculate($this->tmpDir);
        $withElseif = (new IrdCalculator(includeElseif: true))->calculate($this->tmpDir);

        self::assertSame(1, $withoutElseif->ifCount);
        self::assertSame(2, $withElseif->ifCount);
    }

    public function testIncludeTernary(): void
    {
        $this->writeFile('test.php', <<<'PHP'
<?php
$result = true ? 'a' : 'b';
if (true) {
    echo 'a';
}
PHP);

        $without = (new IrdCalculator())->calculate($this->tmpDir);
        $with = (new IrdCalculator(includeTernary: true))->calculate($this->tmpDir);

        self::assertSame(1, $without->ifCount);
        self::assertSame(2, $with->ifCount);
    }

    public function testIncludeMatch(): void
    {
        $this->writeFile('test.php', <<<'PHP'
<?php
$result = match(true) {
    default => 'a',
};
if (true) {
    echo 'a';
}
PHP);

        $without = (new IrdCalculator())->calculate($this->tmpDir);
        $with = (new IrdCalculator(includeMatch: true))->calculate($this->tmpDir);

        self::assertSame(1, $without->ifCount);
        self::assertSame(2, $with->ifCount);
    }

    public function testDirectoryNotFound(): void
    {
        $calculator = new IrdCalculator();

        $this->expectException(\InvalidArgumentException::class);
        $calculator->calculate('/non/existent/directory');
    }

    public function testNonPhpFilesAreIgnored(): void
    {
        $this->writeFile('test.php', '<?php if (true) {}');
        $this->writeFile('test.txt', 'if (true) {}');
        $this->writeFile('test.js', 'if (true) {}');

        $calculator = new IrdCalculator();
        $result = $calculator->calculate($this->tmpDir);

        self::assertSame(1, $result->ifCount);
        self::assertSame(1, $result->files);
    }

    public function testOnFileCallback(): void
    {
        $this->writeFile('a.php', '<?php if (true) {}');
        $this->writeFile('b.php', '<?php if (true) {}');

        $visited = [];
        $calculator = new IrdCalculator();
        $calculator->calculate($this->tmpDir, function (string $path) use (&$visited) {
            $visited[] = basename($path);
        });

        self::assertCount(2, $visited);
    }

    private function writeFile(string $relativePath, string $content): void
    {
        $fullPath = $this->tmpDir . '/' . $relativePath;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($fullPath, $content);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}

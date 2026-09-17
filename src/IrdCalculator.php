<?php

declare(strict_types=1);

namespace CentaurVova\IrdMeter;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

final class IrdCalculator
{
    public function __construct(
        private readonly bool $includeElseif = false,
        private readonly bool $includeTernary = false,
        private readonly bool $includeMatch = false,
        private readonly bool $ignoreComments = false,
        private readonly array $exclude = ['vendor', '.git', 'node_modules', 'var/cache'],
    ) {
    }

    public function calculate(string $dir, ?callable $onFile = null): IrdResult
    {
        $dir = realpath($dir);
        if ($dir === false || !is_dir($dir)) {
            throw new \InvalidArgumentException("Directory not found: {$dir}");
        }

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $traverser = new NodeTraverser();

        $filter = new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            fn (\SplFileInfo $file) => !(
                $file->isDir() && in_array($file->getFilename(), $this->exclude, true)
            )
        );

        $iterator = new \RecursiveIteratorIterator($filter);

        $ifWeight = 0;
        $totalLines = 0;
        $files = 0;

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $code = file_get_contents($file->getPathname());
            if ($code === false) {
                continue;
            }

            if ($onFile !== null) {
                $onFile($file->getPathname());
            }

            $totalLines += $this->ignoreComments
                ? $this->countNonCommentLines($code)
                : substr_count($code, PHP_EOL) + 1;

            $files++;

            try {
                $ast = $parser->parse($code);
                if ($ast === null) {
                    continue;
                }

                $ifWeight += BranchCounterBuilder::create()
                    ->addCondition(Node\Stmt\If_::class, 1)
                    ->addCondition(Node\Stmt\ElseIf_::class, (int) $this->includeElseif)
                    ->addCondition(Node\Expr\Ternary::class, (int) $this->includeTernary)
                    ->addCondition(Node\Expr\Match_::class, (int) $this->includeMatch)
                    ->weight($ast);
            } catch (\Throwable) {
                // Skip files with syntax errors
            }
        }

        $density = $totalLines > 0 ? ($ifWeight * 100 / $totalLines) : 0.0;

        return new IrdResult(
            ifWeight: $ifWeight,
            totalLines: $totalLines,
            files: $files,
            density: round($density, 2),
        );
    }

    /**
     * Counts non-comment, non-empty lines using token_get_all.
     */
    private function countNonCommentLines(string $code): int
    {
        $tokens = token_get_all($code);
        $lines = [];
        $currentLine = 1;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $tokenType = $token[0];
                $tokenText = $token[1];
                $tokenLine = $token[2];

                // Skip comments and docblocks
                if (in_array($tokenType, [T_COMMENT, T_DOC_COMMENT], true)) {
                    $currentLine += substr_count($tokenText, "\n");
                    continue;
                }

                // Skip whitespace-only lines
                if ($tokenType === T_WHITESPACE) {
                    $currentLine += substr_count($tokenText, "\n");
                    continue;
                }

                // Significant token — mark the line
                $lineSpan = substr_count($tokenText, "\n");
                for ($i = 0; $i <= $lineSpan; $i++) {
                    $lines[$tokenLine + $i] = true;
                }
                $currentLine = $tokenLine + $lineSpan;
            } else {
                // Single-char token (e.g. `;`, `{`) — mark the line
                $lines[$currentLine] = true;
            }
        }

        return count($lines);
    }
}

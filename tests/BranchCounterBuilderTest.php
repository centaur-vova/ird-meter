<?php

declare(strict_types=1);

namespace CentaurVova\IrdMeter\Tests;

use CentaurVova\IrdMeter\BranchCounterBuilder;
use PhpParser\Node;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class BranchCounterBuilderTest extends TestCase
{
    public function testCreateReturnsInstance(): void
    {
        $builder = BranchCounterBuilder::create();
        $this->assertInstanceOf(BranchCounterBuilder::class, $builder);
    }

    public function testAddConditionReturnsNewInstance(): void
    {
        $builder = BranchCounterBuilder::create();
        $newBuilder = $builder->addCondition(Node\Stmt\If_::class);

        $this->assertNotSame($builder, $newBuilder);
    }

    public function testAddConditionThrowsOnInvalidClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('stdClass is not a Node');

        BranchCounterBuilder::create()->addCondition(\stdClass::class);
    }

    public function testWeightCountsIfNodes(): void
    {
        $ast = $this->parse('<?php if (true) {} if (false) {}');

        $weight = BranchCounterBuilder::create()
            ->addCondition(Node\Stmt\If_::class)
            ->weight($ast);

        $this->assertSame(2, $weight);
    }

    public function testWeightUsesCustomWeights(): void
    {
        $ast = $this->parse('<?php if (true) {}');

        $weight = BranchCounterBuilder::create()
            ->addCondition(Node\Stmt\If_::class, 5)
            ->weight($ast);

        $this->assertSame(5, $weight);
    }

    public function testWeightOnEmptyAst(): void
    {
        $weight = BranchCounterBuilder::create()
            ->addCondition(Node\Stmt\If_::class)
            ->weight([]);

        $this->assertSame(0, $weight);
    }

    public function testWeightCountsElseifNodes(): void
    {
        // `elseif` is a single ElseIf_ node nested in If_
        $ast = $this->parse('<?php if (true) {} elseif (false) {}');

        $weight = BranchCounterBuilder::create()
            ->addCondition(Node\Stmt\If_::class)
            ->weight($ast);

        $this->assertSame(1, $weight);
    }

    public function testWeightCountsElseIfAsNestedIf(): void
    {
        // `else if` is parsed as `else { if (...) {} }` — two If_ nodes
        $ast = $this->parse('<?php if (true) {} else if (false) {}');

        $weight = BranchCounterBuilder::create()
            ->addCondition(Node\Stmt\If_::class)
            ->weight($ast);

        $this->assertSame(2, $weight);
    }

    public function testWeightSumsMultipleConditions(): void
    {
        $ast = $this->parse('<?php if (true) {} $x = $y ? 1 : 2;');

        $weight = BranchCounterBuilder::create()
            ->addCondition(Node\Stmt\If_::class, 1)
            ->addCondition(Node\Expr\Ternary::class, 2)
            ->weight($ast);

        $this->assertSame(3, $weight);
    }

    /**
     * @return Node\Stmt[]
     */
    private function parse(string $code): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        return $parser->parse($code) ?? [];
    }
}

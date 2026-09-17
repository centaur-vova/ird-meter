<?php

declare(strict_types=1);

namespace CentaurVova\IrdMeter;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

final class BranchCounterBuilder
{
    /**
     * @param array<class-string<Node>, int> $weights
     */
    private function __construct(private readonly array $weights = [])
    {
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param class-string<Node> $nodeClass
     */
    public function addCondition(string $nodeClass, int $weight = 1): self
    {
        if (!is_subclass_of($nodeClass, Node::class)) {
            throw new \InvalidArgumentException("$nodeClass is not a Node");
        }

        return new self([...$this->weights, $nodeClass => $weight]);
    }

    /**
     * @param Node\Stmt[] $ast
     */
    public function weight(array $ast): int
    {
        $visitor = $this->build();

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->weight;
    }

    private function build(): NodeVisitor
    {
        return new class ($this->weights) extends NodeVisitorAbstract {
            public int $weight = 0;

            /**
             * @param array<class-string<Node>, int> $weights
             */
            public function __construct(private readonly array $weights)
            {
            }

            public function enterNode(Node $node): ?Node
            {
                $this->weight += $this->weights[$node::class] ?? 0;
                return null;
            }
        };
    }
}

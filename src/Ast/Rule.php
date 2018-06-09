<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Ast;

/**
 * Class Rule
 */
class Rule extends Node implements RuleInterface
{
    /**
     * @var array|iterable|\Traversable
     */
    protected $children;

    /**
     * Rule constructor.
     * @param string $name
     * @param iterable $children
     */
    public function __construct(string $name, iterable $children = [], int $offset = 0)
    {
        parent::__construct($name, $offset);
        $this->children = $children;
    }

    /**
     * @return iterable
     */
    public function getChildren(): iterable
    {
        if ($this->children instanceof \Traversable) {
            $this->children = \iterator_to_array($this->children);
        }

        return $this->children;
    }

    /**
     * @param int $index
     * @return null|NodeInterface
     */
    public function getChild(int $index): ?NodeInterface
    {
        return $this->getChildren()[$index] ?? null;
    }

    /**
     * @param NodeInterface $node
     */
    public function append(NodeInterface $node): void
    {
        $this->getChildren();

        $this->children[] = $node;
    }

    /**
     * @return null|NodeInterface
     */
    public function pop(): ?NodeInterface
    {
        return \count($this->children) ? \array_pop($this->children) : null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return \count($this->getChildren());
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        yield from $this->getChildren();
    }
}

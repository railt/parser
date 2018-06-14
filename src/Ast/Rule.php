<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Ast;
use Railt\Parser\Ast\Finder\Selection;

/**
 * Class Rule
 */
class Rule extends Node implements RuleInterface
{
    /**
     * @var array|iterable|\Traversable
     */
    private $children;

    /**
     * Rule constructor.
     * @param string $name
     * @param iterable $children
     * @param int $offset
     */
    public function __construct(string $name, iterable $children = [], int $offset = 0)
    {
        parent::__construct($name, $offset);

        $this->children = $children;
    }

    /**
     * @return iterable|NodeInterface[]
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

    /**
     * @param string $name
     * @param int|null $depth
     * @return iterable
     */
    public function find(string $name, int $depth = null): iterable
    {
        $depth = \max(0, $depth ?? \PHP_INT_MAX);

        foreach ($this->getChildren() as $child) {
            if ($child->getName() === $name) {
                yield $child;
                continue;
            }

            if ($depth > 0 && $child instanceof RuleInterface) {
                yield from $child->find($name, $depth - 1);
            }
        }
    }

    /**
     * @param string $name
     * @param int|null $depth
     * @return null|NodeInterface
     */
    public function first(string $name, int $depth = null): ?NodeInterface
    {
        return $this->find($name, $depth)->current();
    }
}

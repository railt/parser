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
 * Class Finder
 */
class Finder implements \IteratorAggregate
{
    /**
     * @var RuleInterface|NodeInterface[]
     */
    private $nodes;

    /**
     * Finder constructor.
     * @param RuleInterface|NodeInterface[]|iterable $nodes
     */
    public function __construct(iterable $nodes)
    {
        $this->nodes = $nodes;
    }

    /**
     * @param string $query
     * @param int $maxDepth
     * @return \Traversable|\Generator|RuleInterface[]
     */
    public function query(string $query, int $maxDepth = \INF): \Traversable
    {
        throw new \LogicException(__METHOD__ . ' not implemented yet');
    }

    /**
     * Should move deep into selected slice.
     *
     * @param int $deep
     * @return Finder
     */
    public function deep(int $deep): Finder
    {
        throw new \LogicException(__METHOD__ . ' not implemented yet');
    }

    /**
     * @param string $query
     * @param int $maxDepth
     * @return null|NodeInterface
     */
    public function first(string $query, int $maxDepth = \INF): ?NodeInterface
    {
        $result = $this->query($query, $maxDepth);

        return $result->valid() ? $result->current() : null;
    }

    /**
     * @param string $name
     * @return Finder
     */
    public function except(string $name): Finder
    {
        return $this->each(function (RuleInterface $rule) use ($name): bool {
            return $name !== $rule->getName();
        });
    }

    /**
     * @param string $name
     * @return Finder
     */
    public function filter(string $name): Finder
    {
        return $this->each(function (RuleInterface $rule) use ($name): bool {
            return $name === $rule->getName();
        });
    }

    /**
     * @param \Closure $filter
     * @param int $deep
     * @return Finder
     */
    public function each(\Closure $filter, int $deep = \INF): Finder
    {
        $result = [];

        foreach ($this->nodes as $child) {
            if ($filter($child)) {
                $result[] = $child;

                // TODO Add deep support
            }
        }

        return new static($result);
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        yield from $this->nodes;
    }
}

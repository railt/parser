<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Ast;

use Railt\Lexer\Result\Eoi;

/**
 * Class Rule
 */
class Rule extends Node implements RuleInterface, \ArrayAccess
{
    /**
     * @var array|iterable|\Traversable
     */
    private $children;

    /**
     * Rule constructor.
     * @param string $name
     * @param array|NodeInterface[] $children
     * @param int $offset
     */
    public function __construct(string $name, array $children = [], int $offset = 0)
    {
        parent::__construct($name, $offset);

        $this->children = $children;
    }

    /**
     * @param int $index
     * @return LeafInterface|RuleInterface|NodeInterface
     */
    public function getChild(int $index): NodeInterface
    {
        return $this->getChildren()[$index] ?? new Leaf(new Eoi($this->offset));
    }

    /**
     * @return iterable|LeafInterface[]|RuleInterface[]|NodeInterface[]
     */
    public function getChildren(): iterable
    {
        return $this->children;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return \count($this->getChildren());
    }

    /**
     * @return \Traversable|LeafInterface[]|RuleInterface[]
     */
    public function getIterator(): \Traversable
    {
        yield from $this->getChildren();
    }

    /**
     * @param int $group
     * @return null|string
     */
    public function getValue(int $group = 0): ?string
    {
        $result = '';

        foreach ($this->getChildren() as $child) {
            $result .= $child->getValue($group);
        }

        return $result;
    }

    /**
     * @return iterable|string[]|\Generator
     */
    public function getValues(): iterable
    {
        foreach ($this->getChildren() as $child) {
            yield from $child->getValues();
        }
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        \assert(\is_int($offset));

        return isset($this->children[$offset]);
    }

    /**
     * @param int $offset
     * @return LeafInterface|NodeInterface|RuleInterface
     */
    public function offsetGet($offset): NodeInterface
    {
        \assert(\is_int($offset));

        return $this->getChild((int)$offset);
    }

    /**
     * @param int $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        \assert(\is_int($offset));
        \assert($value instanceof RuleInterface);

        $this->children[$offset] = $value;
    }

    /**
     * @param int $offset
     */
    public function offsetUnset($offset): void
    {
        \assert(\is_int($offset));

        unset($this->children[$offset]);
    }
}

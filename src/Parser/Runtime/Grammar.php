<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime;

/**
 * Class Grammar
 */
class Grammar implements GrammarInterface
{
    /**
     * @var int
     */
    public const TYPE_ALTERNATION = 0x00;

    /**
     * @var int
     */
    public const TYPE_CONCATENATION = 0x01;

    /**
     * @var int
     */
    public const TYPE_REPETITION = 0x02;

    /**
     * @var int
     */
    public const TYPE_TERMINAL = 0x03;

    /**
     * @var array|int[]|null
     */
    public $types;

    /**
     * @var array|int[]|null
     */
    public $children;

    /**
     * @var array|null
     */
    public $nodes;

    /**
     * @var array|null
     */
    public $defaults;

    /**
     * @var array|null
     */
    public $transitional;

    /**
     * @var array|null
     */
    public $keep;

    /**
     * @var array|null
     */
    public $min;

    /**
     * @var array|null
     */
    public $max;

    /**
     * @var array|null
     */
    public $tokens;

    /**
     * @var string|null
     */
    public $root;

    /**
     * @return string
     */
    public function rootId(): string
    {
        \assert($this->root !== null, 'Root id should be initialized');

        return $this->root;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isTerminal($id): bool
    {
        \assert($this->types !== null, 'Types list should be initialized');

        return $this->types[$id] === self::TYPE_TERMINAL;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isConcatenation($id): bool
    {
        \assert($this->types !== null, 'Types list should be initialized');

        return $this->types[$id] === self::TYPE_CONCATENATION;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isAlternation($id): bool
    {
        \assert($this->types !== null, 'Types list should be initialized');

        return $this->types[$id] === self::TYPE_ALTERNATION;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isRepetition($id): bool
    {
        \assert($this->types !== null, 'Types list should be initialized');

        return $this->types[$id] === self::TYPE_REPETITION;
    }

    /**
     * @param string|int $id
     * @return string|null
     */
    public function getNodeId($id): ?string
    {
        \assert($this->nodes !== null, 'Nodes list should be initialized');

        return $this->nodes[$id];
    }

    /**
     * @param string|int $id
     * @return string|null
     */
    public function getDefaultId($id): ?string
    {
        \assert($this->types !== null, 'Defaults list should be initialized');

        return $this->defaults[$id];
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isTransitional($id): bool
    {
        \assert($this->types !== null, 'Transitional list should be initialized');

        return $this->transitional[$id];
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isKept($id): bool
    {
        \assert($this->types !== null, 'Kept list should be initialized');

        return $this->keep[$id];
    }

    /**
     * @param string|int $id
     * @return string
     */
    public function getTokenName($id): string
    {
        \assert($this->types !== null, 'Token names list should be initialized');

        return $this->tokens[$id];
    }

    /**
     * @param string|int $id
     * @return int|int[]|string|string[]
     */
    public function getChildren($id)
    {
        \assert($this->types !== null, 'Children list should be initialized');

        return $this->children[$id];
    }

    /**
     * @param string|int $id
     * @return int
     */
    public function getMin($id): int
    {
        \assert($this->types !== null, 'Min ids list should be initialized');

        return $this->min[$id];
    }

    /**
     * @param string|int $id
     * @return int
     */
    public function getMax($id): int
    {
        \assert($this->types !== null, 'Max ids list should be initialized');

        return $this->max[$id];
    }
}

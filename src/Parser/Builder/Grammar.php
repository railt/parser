<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Builder;

use Railt\Parser\Runtime\GrammarInterface;

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
     * @var array|int[]
     */
    public $transitional = [];

    /**
     * @var array|int[]
     */
    public $keep = [];

    /**
     * @var int
     */
    public $root;

    /**
     * @var array|array[]
     */
    public $actions = [];

    /**
     * @var array|int[]|array[]
     */
    public $goto = [];

    /**
     * @var int
     */
    public const ACTION_TYPE = 0x00;

    /**
     * @var int
     */
    public const ACTION_NAME = 0x01;

    /**
     * @var array|int[][]
     */
    public $repeat = [];

    /**
     * @var int
     */
    public const REPEAT_MIN = 0x00;

    /**
     * @var int
     */
    public const REPEAT_MAX = 0x01;


    /**
     * @return int|mixed|string|null
     */
    public function rootId()
    {
        \assert($this->root !== null, 'Root ID should be initialized');

        return $this->root;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isTerminal($id): bool
    {
        return $this->actions[$id][self::ACTION_TYPE] === self::TYPE_TERMINAL;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isConcatenation($id): bool
    {
        return $this->actions[$id][self::ACTION_TYPE] === self::TYPE_CONCATENATION;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isAlternation($id): bool
    {
        return $this->actions[$id][self::ACTION_TYPE] === self::TYPE_ALTERNATION;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isRepetition($id): bool
    {
        return $this->actions[$id][self::ACTION_TYPE] === self::TYPE_REPETITION;
    }

    /**
     * @param string|int $id
     * @return string|null
     */
    public function getNodeId($id): ?string
    {
        return $this->actions[$id][self::ACTION_NAME] ?? null;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isTransitional($id): bool
    {
        return \in_array($id, $this->transitional, true);
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isKept($id): bool
    {
        return \in_array($id, $this->keep, true);
    }

    /**
     * @param string|int $id
     * @return string
     */
    public function getTokenName($id): string
    {
        return $this->actions[$id][self::ACTION_NAME];
    }

    /**
     * @param string|int $id
     * @return int|int[]|string|string[]
     */
    public function getChildren($id)
    {
        return $this->goto[$id];
    }

    /**
     * @param string|int $id
     * @return int
     */
    public function getMin($id): int
    {
        return $this->repeat[$id][self::REPEAT_MIN];
    }

    /**
     * @param string|int $id
     * @return int
     */
    public function getMax($id): int
    {
        return $this->repeat[$id][self::REPEAT_MAX];
    }
}

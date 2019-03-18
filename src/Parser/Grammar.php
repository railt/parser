<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser;

use Railt\Parser\Builder\Definition\Alternation;
use Railt\Parser\Builder\Definition\Concatenation;
use Railt\Parser\Builder\Definition\Repetition;
use Railt\Parser\Builder\Definition\Rule;
use Railt\Parser\Builder\Definition\Terminal;

/**
 * Class Grammar
 */
class Grammar
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
     * @var array|Rule[]|Terminal[]|Repetition[]
     */
    private $definitions;

    /**
     * @var string
     */
    protected $root;

    /**
     * Grammar constructor.
     *
     * @param array $rules
     * @param string $root
     */
    public function __construct(array $rules, string $root)
    {
        $this->definitions = $rules;
        $this->root = $root;
    }

    private function build(array $definitions)
    {

    }

    /**
     * @return string
     */
    public function rootId(): string
    {
        return $this->root;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isTerminal($id): bool
    {
        return $this->definitions[$id] instanceof Terminal;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isConcatenation($id): bool
    {
        return $this->definitions[$id] instanceof Concatenation;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isAlternation($id): bool
    {
        return $this->definitions[$id] instanceof Alternation;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isRepetition($id): bool
    {
        return $this->definitions[$id] instanceof Repetition;
    }

    /**
     * @param string|int $id
     * @return string|null
     */
    public function getNodeId($id): ?string
    {
        return $this->definitions[$id]->getNodeId();
    }

    /**
     * @param string|int $id
     * @return string|null
     */
    public function getDefaultId($id): ?string
    {
        return $this->definitions[$id]->getDefaultId();
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isTransitional($id): bool
    {
        return \is_int($id);
    }

    /**
     * @param string|int $id
     * @return bool
     */
    public function isKept($id): bool
    {
        return $this->definitions[$id]->isKept();
    }

    /**
     * @param string|int $id
     * @return string
     */
    public function getTokenName($id): string
    {
        return $this->definitions[$id]->getTokenName();
    }

    /**
     * @param string|int $id
     * @return int|int[]|string|string[]
     */
    public function getChildren($id)
    {
        return $this->definitions[$id]->getChildren();
    }

    /**
     * @param string|int $id
     * @return int
     */
    public function getMin($id): int
    {
        return $this->definitions[$id]->getMin();
    }

    /**
     * @param string|int $id
     * @return int
     */
    public function getMax($id): int
    {
        return $this->definitions[$id]->getMax();
    }
}

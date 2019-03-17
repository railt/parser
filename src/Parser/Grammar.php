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
     * @var array|Rule[]
     */
    private $definitions;

    /**
     * @var string
     */
    private $root;

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

    /**
     * @return string
     */
    public function rootId(): string
    {
        return $this->root;
    }

    /**
     * @param int|string $rule
     * @return Rule
     */
    public function get($rule): Rule
    {
        return $this->definitions[$rule];
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
}

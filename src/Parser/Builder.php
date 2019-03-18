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
use Railt\Parser\Builder\DefinitionInterface;
use Railt\Parser\Builder\LexemeDefinitionInterface;
use Railt\Parser\Builder\Definition\Repetition;
use Railt\Parser\Builder\Definition\Rule;
use Railt\Parser\Builder\Definition\Terminal;
use Railt\Parser\Runtime\GrammarInterface;

/**
 * Class Builder
 */
class Builder implements BuilderInterface, GrammarInterface
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
     * @var array
     */
    private $aliases = [];

    /**
     * @var array
     */
    private $transitional = [];

    /**
     * @var array
     */
    private $keep = [];

    /**
     * @var int
     */
    private $root;

    /**
     * @var array
     */
    private $mappings = [];

    /**
     * @var array
     */
    private $actions = [];

    /**
     * @var array
     */
    private $goto = [];

    /**
     * @var int
     */
    private const ACTION_TYPE = 0x00;

    /**
     * @var int
     */
    private const ACTION_NAME = 0x01;

    /**
     * @var array
     */
    private $repeat = [];

    /**
     * @var int
     */
    private const REPEAT_MIN = 0x00;

    /**
     * @var int
     */
    private const REPEAT_MAX = 0x01;

    /**
     * Builder constructor.
     *
     * @param array|Rule[] $rules
     * @param string $root
     */
    public function __construct(array $rules, string $root)
    {
        foreach ($rules as $rule) {
            $this->mappings[$rule->getName()] = \count($this->mappings);
        }

        $this->root = $this->mappings[$root];
        $this->create($rules);
    }

    /**
     * @param iterable|Rule[] $rules
     * @return BuilderInterface|$this
     */
    public function create(iterable $rules): BuilderInterface
    {
        $type = function ($rule): int {
            switch (true) {
                case $rule instanceof Alternation:
                    return self::TYPE_ALTERNATION;
                    break;
                case $rule instanceof Concatenation:
                    return self::TYPE_CONCATENATION;
                    break;
                case $rule instanceof Repetition:
                    return self::TYPE_REPETITION;
                    break;
                case $rule instanceof Terminal:
                    return self::TYPE_TERMINAL;
                    break;
                default:
                    return 0;
            }
        };

        $map = function ($children) {
            if (\is_array($children)) {
                $result = [];
                foreach ($children as $child) {
                    $result[] = $this->mappings[$child];
                }
                return $result;
            }

            if ($children) {
                return $this->mappings[$children];
            }

            return null;
        };

        foreach ($rules as $rule) {
            $index = $this->mappings[$rule->getName()];

            if ($rule instanceof Terminal) {
                $this->actions[$index] = [
                    self::ACTION_TYPE => $type($rule),
                    self::ACTION_NAME => $rule->getTokenName()
                ];

                if ($rule->isKept()) {
                    $this->keep[] = $index;
                }

                $this->goto[$index] = null;

                continue;
            }

            if ($rule instanceof Repetition) {
                $this->repeat[$index] = [
                    self::REPEAT_MIN => $rule->getMin(),
                    self::REPEAT_MAX => $rule->getMax(),
                ];
            }

            if ($rule instanceof Rule) {
                $this->actions[$index] = [
                    self::ACTION_TYPE => $type($rule),
                    self::ACTION_NAME => $rule->getNodeId()
                ];

                $this->goto[$index] = $map($rule->getChildren());

                if ($rule->getDefaultId()) {
                    $this->aliases[$index] = $rule->getDefaultId();
                }

                if (\is_int($rule->getName())) {
                    $this->transitional[] = $index;
                }
            }
        }

        return $this;
    }

    /**
     * @return int|mixed|string|null
     */
    public function rootId()
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
     * @return string|null
     */
    public function getDefaultId($id): ?string
    {
        return $this->aliases[$id] ?? null;
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

    /**
     * @return GrammarInterface
     */
    public function getGrammar(): GrammarInterface
    {
        return $this;
    }
}

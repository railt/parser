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
use Railt\Parser\Builder\Definition\Lexeme;
use Railt\Parser\Builder\DefinitionInterface;
use Railt\Parser\Builder\Definition\Repetition;
use Railt\Parser\Builder\Grammar;
use Railt\Parser\Builder\ProductionDefinitionInterface;
use Railt\Parser\Runtime\GrammarInterface;

/**
 * Class Builder
 */
class Builder implements BuilderInterface
{
    /**
     * @var int|string
     */
    private $root;

    /**
     * @var array
     */
    private $definitions = [];

    /**
     * Builder constructor.
     *
     * @param array|DefinitionInterface[] $definitions
     * @param string|int $root
     */
    public function __construct(array $definitions, $root)
    {
        $this->root = $root;
        $this->addMany($definitions);
    }

    /**
     * @param iterable|DefinitionInterface[] $definitions
     * @return BuilderInterface|$this
     */
    public function addMany(iterable $definitions): BuilderInterface
    {
        foreach ($definitions as $definition) {
            $this->add($definition);
        }

        return $this;
    }

    /**
     * @param DefinitionInterface $definition
     * @return BuilderInterface|$this
     */
    public function add(DefinitionInterface $definition): BuilderInterface
    {
        $this->definitions[] = $definition;

        return $this;
    }

    /**
     * @return array
     */
    private function getMappings(): array
    {
        $mappings = [];

        foreach ($this->definitions as $definition) {
            $mappings[$definition->getId()] = \count($mappings);
        }

        return $mappings;
    }

    /**
     * @param DefinitionInterface $definition
     * @return int
     */
    private function getType(DefinitionInterface $definition): int
    {
        switch (true) {
            case $definition instanceof Alternation:
                return Grammar::TYPE_ALTERNATION;
                break;

            case $definition instanceof Concatenation:
                return Grammar::TYPE_CONCATENATION;
                break;

            case $definition instanceof Repetition:
                return Grammar::TYPE_REPETITION;
                break;

            case $definition instanceof Lexeme:
                return Grammar::TYPE_TERMINAL;
                break;
            default:
                return 0;
        }
    }

    /**
     * @param array|int|string $children
     * @param array $mappings
     * @return int|int[]
     */
    private function map($children, array $mappings)
    {
        if (\is_array($children)) {
            $result = [];

            foreach ($children as $child) {
                $result[] = $mappings[$child];
            }

            return $result;
        }

        return $mappings[$children];
    }

    /**
     * @return GrammarInterface
     */
    public function getGrammar(): GrammarInterface
    {
        $mappings = $this->getMappings();

        $grammar = new Grammar();
        $grammar->root = $mappings[$this->root];

        foreach ($this->definitions as $rule) {
            $index = $this->map($rule->getId(), $mappings);

            if ($rule instanceof Lexeme) {
                $grammar->actions[$index] = [
                    Grammar::ACTION_TYPE => $this->getType($rule),
                    Grammar::ACTION_NAME => $rule->getName()
                ];

                if ($rule->isKept()) {
                    $grammar->keep[] = $index;
                }

                $grammar->goto[$index] = null;

                continue;
            }

            if ($rule instanceof Repetition) {
                $grammar->repeat[$index] = [
                    Grammar::REPEAT_MIN => $rule->getMin(),
                    Grammar::REPEAT_MAX => $rule->getMax(),
                ];
            }

            if ($rule instanceof ProductionDefinitionInterface) {
                $grammar->actions[$index] = [
                    Grammar::ACTION_TYPE => $this->getType($rule),
                    Grammar::ACTION_NAME => $rule->getAlias()
                ];

                $grammar->goto[$index] = $this->map($rule->getGoto(), $mappings);

                if (\is_int($rule->getId())) {
                    $grammar->transitional[] = $index;
                }
            }
        }

        return $grammar;
    }
}

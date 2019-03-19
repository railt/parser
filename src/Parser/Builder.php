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
use Railt\Parser\Builder\Definition\Repetition;
use Railt\Parser\Builder\DefinitionInterface;
use Railt\Parser\Builder\ProductionDefinitionInterface;
use Railt\Parser\Runtime\Grammar;
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
    public function __construct(array $definitions = [], $root = null)
    {
        $this->addMany($definitions);
        $this->startsAt($root);
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
     * @param int|string $name
     * @return BuilderInterface|$this
     */
    public function startsAt($name): BuilderInterface
    {
        $this->root = $name;

        return $this;
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
            switch (true) {
                case $rule instanceof Lexeme:
                    $this->defineLexeme($grammar, $mappings, $rule);
                    break;

                case $rule instanceof Repetition:
                    $this->defineRepetition($grammar, $mappings, $rule);
                    break;

                case $rule instanceof Alternation:
                    $this->defineAlternation($grammar, $mappings, $rule);
                    break;

                case $rule instanceof Concatenation:
                    $this->defineConcatenation($grammar, $mappings, $rule);
                    break;
            }
        }

        return $grammar;
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
     * @param Grammar $grammar
     * @param array $mappings
     * @param Lexeme $lexeme
     */
    private function defineLexeme(Grammar $grammar, array $mappings, Lexeme $lexeme): void
    {
        $index = $this->map($lexeme->getId(), $mappings);

        $grammar->actions[$index] = Grammar::TYPE_TERMINAL;
        $grammar->names[$index] = $lexeme->getName();
        $grammar->goto[$index] = $lexeme->isKept();
    }

    /**
     * @param string|int $goto
     * @param array $mappings
     * @return int
     */
    private function map($goto, array $mappings): int
    {
        return $mappings[$goto];
    }

    /**
     * @param Grammar $grammar
     * @param array $mappings
     * @param Repetition $repetition
     */
    private function defineRepetition(Grammar $grammar, array $mappings, Repetition $repetition): void
    {
        $index = $this->map($repetition->getId(), $mappings);

        $grammar->actions[$index] = Grammar::TYPE_REPETITION;

        $grammar->goto[$index] = [
            $this->mapMany($repetition->getGoto(), $mappings)[0],
            Grammar::REPEAT_MIN => $repetition->getMin(),
            Grammar::REPEAT_MAX => $repetition->getMax(),
        ];

        $this->defineProduction($grammar, $mappings, $repetition);
    }

    /**
     * @param array|int[]|string[] $goto
     * @param array $mappings
     * @return array|int[]
     */
    private function mapMany(array $goto, array $mappings): array
    {
        return \array_map(function ($rule) use ($mappings) {
            return $mappings[$rule];
        }, $goto);
    }

    /**
     * @param Grammar $grammar
     * @param array $mappings
     * @param ProductionDefinitionInterface $rule
     */
    private function defineProduction(Grammar $grammar, array $mappings, ProductionDefinitionInterface $rule): void
    {
        $index = $this->map($rule->getId(), $mappings);

        if ($rule->getAlias() !== null) {
            $grammar->names[$index] = $rule->getAlias();
        }

        if (\is_int($rule->getId())) {
            $grammar->transitional[] = $this->map($rule->getId(), $mappings);
        }
    }

    /**
     * @param Grammar $grammar
     * @param array $mappings
     * @param Alternation $choice
     */
    private function defineAlternation(Grammar $grammar, array $mappings, Alternation $choice): void
    {
        $index = $this->map($choice->getId(), $mappings);

        $grammar->actions[$index] = Grammar::TYPE_ALTERNATION;
        $grammar->goto[$index] = $this->mapMany($choice->getGoto(), $mappings);

        $this->defineProduction($grammar, $mappings, $choice);
    }

    /**
     * @param Grammar $grammar
     * @param array $mappings
     * @param Concatenation $sequence
     */
    private function defineConcatenation(Grammar $grammar, array $mappings, Concatenation $sequence): void
    {
        $index = $this->map($sequence->getId(), $mappings);

        $grammar->actions[$index] = Grammar::TYPE_CONCATENATION;
        $grammar->goto[$index] = $this->mapMany($sequence->getGoto(), $mappings);

        $this->defineProduction($grammar, $mappings, $sequence);
    }
}

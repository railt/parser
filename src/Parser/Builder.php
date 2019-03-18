<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser;

use Railt\Parser\Builder\Definition\Rule;
use Railt\Parser\Builder\Definition\Alternation;
use Railt\Parser\Builder\Definition\Concatenation;
use Railt\Parser\Builder\Definition\Repetition;
use Railt\Parser\Builder\Definition\Terminal;
use Railt\Parser\Runtime\Grammar;
use Railt\Parser\Runtime\GrammarInterface;

/**
 * Class Builder
 */
class Builder extends Grammar implements BuilderInterface
{
    /**
     * @param int|string $id
     * @return BuilderInterface|$this
     */
    public function startsAt($id): BuilderInterface
    {
        $this->root = $id;

        return $this;
    }

    /**
     * @param Rule $rule
     * @return BuilderInterface|$this
     */
    public function add(Rule $rule): BuilderInterface
    {
        $index = $rule->getName();

        switch (true) {
            case $rule instanceof Alternation:
                $this->types[$index] = self::TYPE_ALTERNATION;
                break;
            case $rule instanceof Concatenation:
                $this->types[$index] = self::TYPE_CONCATENATION;
                break;
            case $rule instanceof Repetition:
                $this->types[$index] = self::TYPE_REPETITION;
                break;
            case $rule instanceof Terminal:
                $this->types[$index] = self::TYPE_TERMINAL;
                break;
        }

        if ($rule instanceof Rule) {
            $this->children[$index] = $rule->getChildren();
            $this->nodes[$index] = $rule->getNodeId();
            $this->defaults[$index] = $rule->getDefaultId();
            $this->transitional[$index] = \is_int($index);
        }

        if ($rule instanceof Terminal) {
            $this->keep[$index] = $rule->isKept();
            $this->tokens[$index] = $rule->getTokenName();
        }

        if ($rule instanceof Repetition) {
            $this->min[$index] = $rule->getMin();
            $this->max[$index] = $rule->getMax();
        }

        return $this;
    }

    /**
     * @param iterable $rules
     * @return BuilderInterface|$this
     */
    public function create(iterable $rules): BuilderInterface
    {
        foreach ($rules as $rule) {
            $this->add($rule);
        }

        return $this;
    }

    /**
     * @return GrammarInterface
     */
    public function getGrammar(): GrammarInterface
    {
        return $this;
    }
}

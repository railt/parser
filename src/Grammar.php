<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser;

use Railt\Parser\Ast\Delegate;
use Railt\Parser\Exception\GrammarException;
use Railt\Parser\Rule\Rule;

/**
 * Class Grammar
 */
class Grammar implements GrammarInterface
{
    /**
     * @var array|Rule[]
     */
    private $rules;

    /**
     * @var string|int
     */
    private $root;

    /**
     * @var array|string[]|Delegate[]
     */
    private $delegates;

    /**
     * Grammar constructor.
     * @param array|Rule[] $rules
     * @param array $delegates
     * @param string|int|null $root
     * @throws GrammarException
     */
    public function __construct(array $rules, $root = null, array $delegates = [])
    {
        $this->addRules(...\array_values($rules));
        $this->delegates = $delegates;
        $this->root      = $root ?? $this->resolveRootRule();
    }

    /**
     * @param Rule ...$rules
     * @return $this|GrammarInterface
     */
    public function addRules(Rule ...$rules): GrammarInterface
    {
        foreach ($rules as $rule) {
            $this->rules[$rule->getName()] = $rule;
        }

        return $this;
    }

    /**
     * @return string
     * @throws GrammarException
     */
    private function resolveRootRule(): string
    {
        foreach ($this->rules as $i => $rule) {
            if (\is_string($rule->getName())) {
                return $rule->getName();
            }
        }

        throw new GrammarException('Unrecognized root rule');
    }

    /**
     * @param string $rule
     * @return string|null
     */
    public function delegate(string $rule): ?string
    {
        return $this->delegates[$rule] ?? null;
    }

    /**
     * @return int|string
     */
    public function beginAt()
    {
        return $this->root;
    }

    /**
     * @param int|string $rule
     * @return Rule
     */
    public function fetch($rule): Rule
    {
        return $this->rules[$rule];
    }

    /**
     * @param array $delegates
     * @return $this|GrammarInterface
     */
    public function addDelegates(array $delegates): GrammarInterface
    {
        foreach ($delegates as $rule => $delegate) {
            $this->delegates[$rule] = $delegate;
        }

        return $this;
    }

    /**
     * @return iterable|string[]|Delegate[]
     */
    public function getDelegates(): iterable
    {
        return $this->delegates;
    }

    /**
     * @return iterable
     */
    public function getRules(): iterable
    {
        return \array_values($this->rules);
    }
}

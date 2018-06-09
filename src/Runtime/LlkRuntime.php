<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime;

use Railt\Io\Readable;
use Railt\Lexer\Result\Eoi;
use Railt\Lexer\TokenInterface;
use Railt\Parser\Exception\UnexpectedTokenException;
use Railt\Parser\Iterator\BufferInterface;
use Railt\Parser\Rule\Alternation;
use Railt\Parser\Rule\Concatenation;
use Railt\Parser\Rule\Production;
use Railt\Parser\Rule\Repetition;
use Railt\Parser\Rule\RulesContainerInterface;
use Railt\Parser\Rule\Symbol;
use Railt\Parser\Rule\Token;
use Railt\Parser\Runtime\Trace\Entry;
use Railt\Parser\Runtime\Trace\Escape;
use Railt\Parser\Runtime\Trace\Invocation;
use Railt\Parser\Runtime\Trace\Terminator;

/**
 * Class Parser
 */
class LlkRuntime implements RuntimeInterface
{
    /**
     * Trace of activated rules.
     *
     * @var array
     */
    protected $trace = [];

    /**
     * Then
     * @var array
     */
    protected $todo;

    /**
     * Current depth while building the trace.
     *
     * @var int
     */
    protected $depth = -1;

    /**
     * @var RulesContainerInterface
     */
    private $rules;

    /**
     * @var Production
     */
    private $root;

    /**
     * LlkRuntime constructor.
     * @param RulesContainerInterface $rules
     * @param Symbol $root
     */
    public function __construct(RulesContainerInterface $rules, Symbol $root)
    {
        $this->rules = $rules;
        $this->root  = $root;
    }

    /**
     * @param Readable $input
     * @param BufferInterface $buffer
     * @return iterable
     */
    public function parse(Readable $input, BufferInterface $buffer): iterable
    {
        $this->trace = [];
        $this->todo  = [];

        $closeRule  = new Escape($this->root, 0);
        $openRule   = new Entry($this->root, 0, [$closeRule]);
        $this->todo = [$closeRule, $openRule];

        do {
            $out = $this->unfold($buffer);

            if ($out !== null && $buffer->current()->name() === Eoi::T_NAME) {
                break;
            }

            if ($this->backtrack($buffer) === false) {
                /** @var TokenInterface $token */
                $token = $buffer->top();

                $error = \sprintf('Unexpected token "%s" (%s)', $token->value(), $token->name());
                throw (new UnexpectedTokenException($error))->throwsIn($input, $token->offset());
            }
        } while (true);

        return $this->trace;
    }

    /**
     * @param BufferInterface $buffer
     * @return bool|null
     */
    protected function unfold(BufferInterface $buffer): ?bool
    {
        while (0 < \count($this->todo)) {
            $rule = \array_pop($this->todo);

            if ($rule instanceof Escape) {
                $rule->setDepth($this->depth);
                $this->trace($rule, $buffer);

                if ($rule->isTransitional() === false) {
                    --$this->depth;
                }
            } else {
                $out = $this->parseCurrentRule($buffer, $this->fetch($rule->getRule()), $rule->getData());

                if ($out === false && $this->backtrack($buffer) === false) {
                    return null;
                }
            }
        }

        return true;
    }

    /**
     * @param Invocation $invocation
     * @param BufferInterface $buffer
     */
    private function trace(Invocation $invocation, BufferInterface $buffer): void
    {
        $this->trace[] = $invocation;

        $invocation->at($buffer->current()->offset());
    }

    /**
     * Parse current rule
     * @param BufferInterface $buffer
     * @param Symbol $rule Current rule.
     * @param int $next Next rule index.
     * @return bool
     */
    protected function parseCurrentRule(BufferInterface $buffer, Symbol $rule, $next): bool
    {
        switch (true) {
            case $rule instanceof Token:
                return $this->parseToken($buffer, $rule);

            case $rule instanceof Concatenation:
                return $this->parseConcatenation($buffer, $rule);

            case $rule instanceof Alternation:
                    return $this->parseAlternation($buffer, $rule, $next);

            case $rule instanceof Repetition:
                return $this->parseRepetition($buffer, $rule, $next);
        }

        return false;
    }

    /**
     * @param BufferInterface $buffer
     * @param Concatenation $concat
     * @return bool
     */
    private function parseConcatenation(BufferInterface $buffer, Concatenation $concat): bool
    {
        $this->trace(new Entry($concat, 0, null, $this->depth), $buffer);
        $children = $concat->then();

        for ($i = \count($children) - 1; $i >= 0; --$i) {
            $this->todo[] = new Escape($this->fetch($children[$i]), 0);
            $this->todo[] = new Entry($this->fetch($children[$i]), 0);
        }

        return true;
    }

    /**
     * @param BufferInterface $buffer
     * @param Token $token
     * @return bool
     */
    private function parseToken(BufferInterface $buffer, Token $token): bool
    {
        $name = $buffer->current()->name();

        if ($token->getName() !== $name) {
            return false;
        }

        $value = $buffer->current()->value();
        $current = $buffer->current();
        $offset = $current->offset();

        \array_pop($this->todo);

        $this->trace[] = new Terminator($token->getName(), $value, $offset, $token->isKept());
        $buffer->next();

        return true;
    }

    /**
     * @param BufferInterface $buffer
     * @param Alternation $choice
     * @param int $next
     * @return bool
     */
    private function parseAlternation(BufferInterface $buffer, Alternation $choice, int $next): bool
    {
        $children = $choice->then();

        if ($next >= \count($children)) {
            return false;
        }

        $this->trace(new Entry($choice, $next, $this->todo, $this->depth), $buffer);

        $this->todo[] = new Escape($this->fetch($children[$next]), 0);
        $this->todo[] = new Entry($this->fetch($children[$next]), 0);

        return true;
    }

    /**
     * @param BufferInterface $buffer
     * @param Repetition $repeat
     * @param int $next
     * @return bool
     */
    private function parseRepetition(BufferInterface $buffer, Repetition $repeat, int $next): bool
    {
        $nextRule = $repeat->then()[0];

        if ($next === 0) {
            $min  = $repeat->from();

            $this->trace(new Entry($repeat, $min, null, $this->depth), $buffer);
            \array_pop($this->todo);
            $this->todo[] = new Escape($repeat, $min, $this->todo);

            for ($i = 0; $i < $min; ++$i) {
                $this->todo[] = new Escape($this->fetch($nextRule), 0);
                $this->todo[] = new Entry($this->fetch($nextRule), 0);
            }

            return true;
        }

        $max = $repeat->to();

        if ($max !== Repetition::INF_MAX_VALUE && $next > $max) {
            return false;
        }

        $this->todo[] = new Escape($repeat, $next, $this->todo);

        $this->todo[] = new Escape($this->fetch($nextRule), 0);
        $this->todo[] = new Entry($this->fetch($nextRule), 0);

        return true;
    }

    /**
     * @param int $id
     * @return null|Symbol|Production
     */
    private function fetch(int $id): ?Symbol
    {
        return $this->rules->fetch($id);
    }

    /**
     * Backtrack the trace.
     *
     * @param BufferInterface $buffer
     * @return bool
     */
    protected function backtrack(BufferInterface $buffer): bool
    {
        $found = false;

        do {
            $last = \array_pop($this->trace);

            if ($last instanceof Entry) {
                $found = $this->fetch($last->getRule()) instanceof Alternation;
            } elseif ($last instanceof Escape) {
                $found = $this->fetch($last->getRule()) instanceof Repetition;
            } elseif ($last instanceof Terminator) {
                $buffer->previous();

                if ($buffer->valid() === false) {
                    return false;
                }
            }
        } while (0 < \count($this->trace) && $found === false);

        if ($found === false) {
            return false;
        }

        $this->depth  = $last->getDepth();
        $this->todo   = $last->getTodo();
        $this->todo[] = new Entry($this->fetch($last->getRule()), $last->getData() + 1);

        return true;
    }
}

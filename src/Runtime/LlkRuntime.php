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
use Railt\Parser\Runtime\Trace\TokenTrace;
use Railt\Parser\Runtime\Trace\TraceInterface;

/**
 * Class Parser
 */
class LlkRuntime implements RuntimeInterface
{
    /**
     * @var array
     */
    private $todo;

    /**
     * Current depth while building the trace.
     *
     * @var int
     */
    private $depth = -1;

    /**
     * @var RulesContainerInterface
     */
    private $rules;

    /**
     * @var Production
     */
    private $root;

    /**
     * @var TraceInterface[]
     */
    private $trace;

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
     * @return void
     */
    private function reset(): void
    {
        $close = new Escape($this->root);
        $entry = new Entry($this->root, 0, [$close]);

        $this->depth = -1;
        $this->trace = [];
        $this->todo  = [$close, $entry];
    }

    /**
     * @param Readable $input
     * @param BufferInterface $buffer
     * @return iterable|TraceInterface[]
     */
    public function parse(Readable $input, BufferInterface $buffer): iterable
    {
        $this->reset();

        do {
            if ($this->isComplete($buffer, $this->unfold($buffer))) {
                break;
            }

            if ($this->backtrack($buffer) === false) {
                $this->throwUnexpectedToken($input, $buffer);
            }

            yield from $this->reduce();
        } while (true);
    }

    /**
     * @return \Traversable|TraceInterface[]
     */
    private function reduce(): \Traversable
    {
        yield from $this->trace;

        $this->trace = [];
    }

    /**
     * @param BufferInterface $buffer
     * @param bool $out
     * @return bool
     */
    private function isComplete(BufferInterface $buffer, bool $out): bool
    {
        return $out && $this->isEoi($buffer->current());
    }

    /**
     * @param TokenInterface $token
     * @return bool
     */
    private function isEoi(TokenInterface $token): bool
    {
        return $token instanceof Eoi;
    }

    /**
     * @param Readable $input
     * @param BufferInterface $buffer
     */
    private function throwUnexpectedToken(Readable $input, BufferInterface $buffer): void
    {
        /** @var TokenInterface $token */
        $token = $buffer->top();

        [$name, $value] = [$token->name(), $token->value()];

        $error = $this->isEoi($token)
            ? \sprintf('Unexpected end of input (%s)', $name)
            : \sprintf('Unexpected token "%s" (%s)', $value, $name);

        throw (new UnexpectedTokenException($error))->throwsIn($input, $token->offset());
    }

    /**
     * @param BufferInterface $buffer
     * @return bool
     */
    protected function unfold(BufferInterface $buffer): bool
    {
        while (\count($this->todo) > 0) {
            $rule = \array_pop($this->todo);

            if ($rule instanceof Escape) {
                $rule->setDepth($this->depth);
                $this->rule($rule, $buffer);

                if (! $rule->isKept()) {
                    --$this->depth;
                }
            } else {
                $out = $this->parseCurrentRule($buffer, $this->fetch($rule->getRuleId()), $rule->getData());

                if ($out === false && $this->backtrack($buffer) === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param Invocation $invocation
     * @param BufferInterface $buffer
     */
    private function rule(Invocation $invocation, BufferInterface $buffer): void
    {
        $this->trace[] = $invocation;

        $invocation->at($buffer->current()->offset());
    }

    /**
     * @param TokenTrace $token
     * @param BufferInterface $buffer
     */
    private function token(TokenTrace $token, BufferInterface $buffer): void
    {
        $this->trace[] = $token;

        $token->at($buffer->current()->offset());
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
     * @param Token $token
     * @return bool
     */
    private function parseToken(BufferInterface $buffer, Token $token): bool
    {
        $name = $buffer->current()->name();

        if ($token->getName() !== $name) {
            return false;
        }

        $value   = $buffer->current()->value();

        \array_pop($this->todo);

        $this->token(new TokenTrace($token->getName(), $value, $token->isKept()), $buffer);
        $buffer->next();

        return true;
    }

    /**
     * @param BufferInterface $buffer
     * @param Concatenation $concat
     * @return bool
     */
    private function parseConcatenation(BufferInterface $buffer, Concatenation $concat): bool
    {
        $this->rule(new Entry($concat, 0, null, $this->depth), $buffer);
        $children = $concat->then();

        for ($i = \count($children) - 1; $i >= 0; --$i) {
            $this->todo[] = new Escape($this->fetch($children[$i]));
            $this->todo[] = new Entry($this->fetch($children[$i]));
        }

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

        $this->rule(new Entry($choice, $next, $this->todo, $this->depth), $buffer);

        $this->todo[] = new Escape($this->fetch($children[$next]));
        $this->todo[] = new Entry($this->fetch($children[$next]));

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
            $min = $repeat->from();

            $this->rule(new Entry($repeat, $min, null, $this->depth), $buffer);
            \array_pop($this->todo);
            $this->todo[] = new Escape($repeat, $min, $this->todo);

            for ($i = 0; $i < $min; ++$i) {
                $this->todo[] = new Escape($this->fetch($nextRule));
                $this->todo[] = new Entry($this->fetch($nextRule));
            }

            return true;
        }

        $max = $repeat->to();

        if ($max !== Repetition::INF_MAX_VALUE && $next > $max) {
            return false;
        }

        $this->todo[] = new Escape($repeat, $next, $this->todo);

        $this->todo[] = new Escape($this->fetch($nextRule));
        $this->todo[] = new Entry($this->fetch($nextRule));

        return true;
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
                $found = $this->fetch($last->getRuleId()) instanceof Alternation;
            } elseif ($last instanceof Escape) {
                $found = $this->fetch($last->getRuleId()) instanceof Repetition;
            } elseif ($last instanceof TokenTrace) {
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
        $this->todo[] = new Entry($this->fetch($last->getRuleId()), $last->getData() + 1);

        return true;
    }
}

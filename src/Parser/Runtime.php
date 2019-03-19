<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser;

use Railt\Lexer\TokenInterface;
use Railt\Parser\Ast\Leaf;
use Railt\Parser\Ast\Node;
use Railt\Parser\Ast\Rule as AstRule;
use Railt\Parser\Ast\RuleInterface;
use Railt\Parser\Exception\RuntimeException;
use Railt\Parser\Runtime\GrammarInterface;
use Railt\Parser\Runtime\StreamInterface;
use Railt\Parser\Runtime\TokenStream;
use Railt\Parser\Runtime\Trace\Entry;
use Railt\Parser\Runtime\Trace\Escape;
use Railt\Parser\Runtime\Trace\Lexeme;
use Railt\Parser\Runtime\Trace\Statement;
use Railt\Parser\Runtime\Trace\TraceItem;

/**
 * Class Runtime
 */
class Runtime implements RuntimeInterface
{
    /**
     * Lexer iterator
     *
     * @var TokenStream
     */
    protected $stream;

    /**
     * Trace of parsed rules
     *
     * @var array|Statement[]|Lexeme[]
     */
    protected $trace = [];

    /**
     * Possible token causing an error
     *
     * @var TokenInterface|null
     */
    private $errorToken;

    /**
     * Stack of items which need to be processed
     *
     * @var array|Statement[]|Lexeme[]
     */
    private $todo;

    /**
     * @var GrammarInterface
     */
    private $grammar;

    /**
     * AbstractParser constructor.
     *
     * @param GrammarInterface $grammar
     */
    public function __construct(GrammarInterface $grammar)
    {
        $this->grammar = $grammar;
    }

    /**
     * @param StreamInterface $stream
     * @return mixed|Node
     * @throws RuntimeException
     */
    public function parse(StreamInterface $stream)
    {
        $this->stream = $stream;
        $this->errorToken = null;
        $this->trace = [];
        $this->todo = [
            $closeRule = new Escape($this->grammar->root, 0),
            $openRule = new Entry($this->grammar->root, 0, [$closeRule]),
        ];

        $this->trace();

        return $this->build();
    }

    /**
     * @return array
     * @throws RuntimeException
     */
    private function trace(): array
    {
        do {
            if ($this->unfold() && $this->stream->isEoi()) {
                break;
            }

            $this->verifyBacktrace();
        } while (true);

        return $this->trace;
    }

    /**
     * Unfold trace.
     *
     * @return bool
     */
    private function unfold(): bool
    {
        while (0 < \count($this->todo)) {
            $trace = \array_pop($this->todo);

            if ($trace instanceof Escape) {
                $this->addTrace($trace);
            } else {
                $out = $this->reduce($trace->getName(), $trace->getState());

                if ($out === false && $this->backtrack() === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param TraceItem $item
     * @return TraceItem
     */
    private function addTrace(TraceItem $item): TraceItem
    {
        $this->trace[] = $item;

        $item->at($this->stream->offset());

        return $item;
    }

    /**
     * @param string|int $current
     * @param int $next
     * @return bool
     */
    private function reduce($current, int $next): bool
    {
        if (! $this->stream->current()) {
            return false;
        }

        switch ($this->grammar->actions[$current]) {
            case GrammarInterface::TYPE_TERMINAL:
                return $this->parseTerminal($current);

            case GrammarInterface::TYPE_CONCATENATION:
                return $this->parseConcatenation($current);

            case GrammarInterface::TYPE_ALTERNATION:
                return $this->parseAlternation($current, $next);

            case GrammarInterface::TYPE_REPETITION:
                return $this->parseRepetition($current, $next);
        }

        return false;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    private function parseTerminal($id): bool
    {
        /** @var TokenInterface $current */
        $current = $this->stream->current();

        if ($this->grammar->names[$id] !== $current->getName()) {
            return false;
        }

        \array_pop($this->todo);

        $this->addTrace(new Lexeme($current, $this->grammar->goto[$id]));
        $this->errorToken = $this->stream->next();

        return true;
    }

    /**
     * @param string|int $id
     * @return bool
     */
    private function parseConcatenation($id): bool
    {
        $this->addTrace(new Entry($id));

        foreach (\array_reverse($this->grammar->goto[$id]) as $child) {
            $this->todo[] = new Escape($child);
            $this->todo[] = new Entry($child);
        }

        return true;
    }

    /**
     * @param string|int $id
     * @param int $next
     * @return bool
     */
    private function parseAlternation($id, int $next): bool
    {
        $children = $this->grammar->goto[$id];

        if ($next >= \count($children)) {
            return false;
        }

        $this->addTrace(new Entry($id, $next, $this->todo));

        $nextRule = $children[$next];

        $this->todo[] = new Escape($nextRule, 0);
        $this->todo[] = new Entry($nextRule, 0);

        return true;
    }

    /**
     * @param string|int $id
     * @param int $next
     * @return bool
     */
    private function parseRepetition($id, int $next): bool
    {
        $nextRule = $this->grammar->goto[$id][0];

        if ($next === 0) {
            $min = $this->grammar->goto[$id][GrammarInterface::REPEAT_MIN];

            $this->addTrace(new Entry($id, $min));

            \array_pop($this->todo);

            $this->todo[] = new Escape($id, $min, $this->todo);

            for ($i = 0; $i < $min; ++$i) {
                $this->todo[] = new Escape($nextRule, 0);
                $this->todo[] = new Entry($nextRule, 0);
            }

            return true;
        }

        $max = $this->grammar->goto[$id][GrammarInterface::REPEAT_MAX];

        if ($max !== -1 && $next > $max) {
            return false;
        }

        $this->todo[] = new Escape($id, $next, $this->todo);
        $this->todo[] = new Escape($nextRule, 0);
        $this->todo[] = new Entry($nextRule, 0);

        return true;
    }

    /**
     * Backtrack the trace.
     *
     * @return bool
     */
    private function backtrack(): bool
    {
        $found = false;

        do {
            $last = \array_pop($this->trace);
            if ($last instanceof Entry) {
                $type = $this->grammar->actions[$last->getName()];
                $found = $type === GrammarInterface::TYPE_ALTERNATION;

            } elseif ($last instanceof Escape) {
                $type = $this->grammar->actions[$last->getName()];
                $found = $type === GrammarInterface::TYPE_REPETITION;

            } elseif ($last instanceof Lexeme && ! $this->stream->prev()) {
                return false;
            }
        } while (0 < \count($this->trace) && $found === false);

        if ($found === false) {
            return false;
        }

        $this->todo = $last->goto();
        $this->todo[] = new Entry($last->getName(), $last->getState() + 1);

        return true;
    }

    /**
     * @throws RuntimeException
     */
    private function verifyBacktrace(): void
    {
        if ($this->backtrack() === false) {
            /** @var TokenInterface $token */
            $token = $this->errorToken ?? $this->stream->current();

            throw new RuntimeException($token);
        }
    }

    /**
     * Build AST from trace.
     * Walk through the trace iteratively and recursively.
     *
     * @param int $i Current trace index.
     * @param array &$children Collected children.
     * @return int|mixed
     */
    protected function build(int $i = 0, array &$children = [])
    {
        $max = \count($this->trace);

        while ($i < $max) {
            $trace = $this->trace[$i];
            $name = $trace->getName();

            if ($trace instanceof Entry) {
                $nextTrace = $this->trace[$i + 1];
                $alias = $this->grammar->names[$name] ?? null;
                $transitional = \in_array($name, $this->grammar->transitional, true);

                // Optimization: Skip empty trace sequence.
                if ($nextTrace instanceof Escape && $name === $nextTrace->getName()) {
                    $i += 2;

                    continue;
                }

                if (! $transitional) {
                    $children[] = $name;
                }

                if ($alias !== null) {
                    $children[] = [$alias];
                }

                /** @var int $i */
                $i = $this->build($i + 1, $children);

                if ($transitional) {
                    continue;
                }

                $handle = [];
                $childId = null;

                do {
                    $pop = \array_pop($children);

                    if (\is_object($pop) === true) {
                        $handle[] = $pop;
                    } elseif (\is_array($pop) && $childId === null) {
                        $childId = \reset($pop);
                    } elseif ($name === $pop) {
                        break;
                    }
                } while ($pop !== null);

                if ($childId === null) {
                    $childId = $alias;
                }

                if ($childId === null) {
                    for ($j = \count($handle) - 1; $j >= 0; --$j) {
                        $children[] = $handle[$j];
                    }

                    continue;
                }

                $children[] = $this->create((string)($alias ?: $childId), \array_reverse($handle), $trace->getOffset());
            } elseif ($trace instanceof Escape) {
                return $i + 1;
            } else {
                if ($trace->isKept()) {
                    $children[] = new Leaf($trace->getName(), $trace->getValue(), $trace->getOffset());
                }

                ++$i;
            }
        }

        return $children[0];
    }

    /**
     * @param string $rule
     * @param array $children
     * @param int $offset
     * @return RuleInterface|mixed
     */
    protected function create(string $rule, array $children, int $offset)
    {
        return new AstRule($rule, $children, $offset);
    }
}

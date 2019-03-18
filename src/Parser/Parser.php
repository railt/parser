<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser;

use Railt\Io\Readable;
use Railt\Lexer\LexerInterface;
use Railt\Lexer\Token\Unknown;
use Railt\Lexer\TokenInterface;
use Railt\Parser\Ast\Leaf;
use Railt\Parser\Ast\Node;
use Railt\Parser\Ast\Rule as AstRule;
use Railt\Parser\Ast\RuleInterface;
use Railt\Parser\Exception\UnexpectedTokenException;
use Railt\Parser\Runtime\GrammarInterface;
use Railt\Parser\Runtime\TokenStream;
use Railt\Parser\Runtime\Trace\Entry;
use Railt\Parser\Runtime\Trace\Escape;
use Railt\Parser\Runtime\Trace\Lexeme;
use Railt\Parser\Runtime\Trace\Statement;
use Railt\Parser\Runtime\Trace\TraceItem;

/**
 * Class Parser
 */
class Parser implements ParserInterface
{
    /**
     * @var LexerInterface
     */
    protected $lexer;

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
     * @param LexerInterface $lexer
     * @param GrammarInterface $grammar
     */
    public function __construct(LexerInterface $lexer, GrammarInterface $grammar)
    {
        $this->lexer = $lexer;
        $this->grammar = $grammar;
    }

    /**
     * @param Readable $input
     * @return mixed|RuleInterface
     * @throws \LogicException
     * @throws \Railt\Io\Exception\ExternalFileException
     */
    public function parse(Readable $input)
    {
        $this->trace($input);

        return $this->build();
    }

    /**
     * @param Readable $input
     * @return array
     * @throws \Railt\Io\Exception\ExternalFileException
     */
    private function trace(Readable $input): array
    {
        $this->reset($input);

        do {
            if ($this->unfold() && $this->stream->isEoi()) {
                break;
            }

            $this->verifyBacktrace($input);
        } while (true);

        return $this->trace;
    }

    /**
     * @param Readable $input
     * @throws \Railt\Io\Exception\ExternalFileException
     */
    private function reset(Readable $input): void
    {
        $this->stream = new TokenStream($this->lex($input), \PHP_INT_MAX);

        $this->errorToken = null;

        $this->trace = [];

        $openRule = new Entry($this->grammar->rootId(), 0, [
            $closeRule = new Escape($this->grammar->rootId(), 0),
        ]);

        $this->todo = [$closeRule, $openRule];
    }

    /**
     * @param Readable $input
     * @return iterable|TokenInterface[]
     * @throws UnexpectedTokenException
     */
    private function lex(Readable $input): iterable
    {
        foreach ($this->lexer->lex($input) as $token) {
            if ($token->getName() === Unknown::T_NAME) {
                $exception = new UnexpectedTokenException(\sprintf('Unexpected token %s', $token));
                $exception->throwsIn($input, $token->getOffset());

                throw $exception;
            }

            yield $token;
        }
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

        switch (true) {
            case $this->grammar->isTerminal($current):
                return $this->parseTerminal($current);

            case $this->grammar->isConcatenation($current):
                return $this->parseConcatenation($current);

            case $this->grammar->isAlternation($current):
                return $this->parseAlternation($current, $next);

            case $this->grammar->isRepetition($current):
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

        if ($this->grammar->getTokenName($id) !== $current->getName()) {
            return false;
        }

        \array_pop($this->todo);

        $this->addTrace(new Lexeme($current, $this->grammar->isKept($id)));
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

        $children = $this->grammar->getChildren($id);

        foreach (\array_reverse($children) as $child) {
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
        $children = $this->grammar->getChildren($id);

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
        $nextRule = $this->grammar->getChildren($id);

        if ($next === 0) {
            $min = $this->grammar->getMin($id);

            $this->addTrace(new Entry($id, $min));

            \array_pop($this->todo);

            $this->todo[] = new Escape($id, $min, $this->todo);

            for ($i = 0; $i < $min; ++$i) {
                $this->todo[] = new Escape($nextRule, 0);
                $this->todo[] = new Entry($nextRule, 0);
            }

            return true;
        }

        $max = $this->grammar->getMax($id);

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
                $found = $this->grammar->isAlternation($last->getName());
            } elseif ($last instanceof Escape) {
                $found = $this->grammar->isRepetition($last->getName());
            } elseif ($last instanceof Lexeme && ! $this->stream->prev()) {
                return false;
            }
        } while (0 < \count($this->trace) && $found === false);

        if ($found === false) {
            return false;
        }

        $this->todo = $last->getJumps();
        $this->todo[] = new Entry($last->getName(), $last->getState() + 1);

        return true;
    }

    /**
     * @param Readable $input
     * @throws \Railt\Io\Exception\ExternalFileException
     */
    private function verifyBacktrace(Readable $input): void
    {
        if ($this->backtrack() === false) {
            /** @var TokenInterface $token */
            $token = $this->errorToken ?? $this->stream->current();

            $exception = new UnexpectedTokenException(\sprintf('Unexpected token %s', $token));
            $exception->throwsIn($input, $token->getOffset());

            throw $exception;
        }
    }

    /**
     * Build AST from trace.
     * Walk through the trace iteratively and recursively.
     *
     * @param int $i Current trace index.
     * @param array &$children Collected children.
     * @return Node|int|mixed
     */
    protected function build(int $i = 0, array &$children = [])
    {
        $max = \count($this->trace);

        while ($i < $max) {
            $trace = $this->trace[$i];
            $name = $trace->getName();

            if ($trace instanceof Entry) {
                $nextTrace = $this->trace[$i + 1];
                $id = $this->grammar->getNodeId($name);

                // Optimization: Skip empty trace sequence.
                if ($nextTrace instanceof Escape && $name === $nextTrace->getName()) {
                    $i += 2;

                    continue;
                }

                if (! $this->grammar->isTransitional($name)) {
                    $children[] = $name;
                }

                if ($id !== null) {
                    $children[] = [$id];
                }

                $i = $this->build($i + 1, $children);

                if ($this->grammar->isTransitional($name)) {
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
                    $childId = $this->grammar->getDefaultId($name);
                }

                if ($childId === null) {
                    for ($j = \count($handle) - 1; $j >= 0; --$j) {
                        $children[] = $handle[$j];
                    }

                    continue;
                }

                $children[] = $this->create((string)($id ?: $childId), \array_reverse($handle), $trace->getOffset());
            } elseif ($trace instanceof Escape) {
                return $i + 1;
            } else {
                if (! $trace->isKept()) {
                    ++$i;
                    continue;
                }

                $children[] = new Leaf($trace->getName(), $trace->getValue(), $trace->getOffset());
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

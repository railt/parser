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
use Railt\Parser\Ast\Rule as AstRule;
use Railt\Parser\Ast\RuleInterface;
use Railt\Parser\Exception\UnexpectedTokenException;
use Railt\Parser\Rule\Alternation;
use Railt\Parser\Rule\Concatenation;
use Railt\Parser\Rule\Repetition;
use Railt\Parser\Rule\Rule;
use Railt\Parser\Rule\Terminal;
use Railt\Parser\Runtime\Builder;
use Railt\Parser\Runtime\TokenStream;
use Railt\Parser\Runtime\Trace\Entry;
use Railt\Parser\Runtime\Trace\Escape;
use Railt\Parser\Runtime\Trace\Statement;
use Railt\Parser\Runtime\Trace\Lexeme;
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
     * Possible token causing an error
     *
     * @var TokenInterface|null
     */
    private $errorToken;

    /**
     * Trace of parsed rules
     *
     * @var array|Statement[]|Lexeme[]
     */
    protected $trace = [];

    /**
     * Stack of items which need to be processed
     *
     * @var array|Statement[]|Lexeme[]
     */
    private $todo;

    /**
     * @var array|Rule[]
     */
    private $rules;

    /**
     * @var string
     */
    private $root;

    /**
     * AbstractParser constructor.
     *
     * @param LexerInterface $lexer
     * @param array $rules
     * @param string $root
     */
    public function __construct(LexerInterface $lexer, array $rules, string $root)
    {
        $this->lexer = $lexer;
        $this->rules = $rules;
        $this->root = $root;
    }

    /**
     * @return LexerInterface
     */
    public function getLexer(): LexerInterface
    {
        return $this->lexer;
    }

    /**
     * @param Readable $input
     * @return mixed|RuleInterface
     * @throws \LogicException
     * @throws \Railt\Io\Exception\ExternalFileException
     */
    public function parse(Readable $input)
    {
        $trace = $this->trace($input);

        $builder = new Builder($trace, $this->rules, \Closure::fromCallable([$this, 'create']));

        return $builder->build();
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

    /**
     * @param Readable $input
     * @return array
     * @throws \Railt\Io\Exception\ExternalFileException
     */
    protected function trace(Readable $input): array
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
        $this->stream = $this->getStream($input);

        $this->errorToken = null;

        $this->trace = [];

        $openRule = new Entry($this->root, 0, [
            $closeRule = new Escape($this->root, 0),
        ]);

        $this->todo = [$closeRule, $openRule];
    }

    /**
     * @param Readable $input
     * @return TokenStream
     * @throws \Railt\Io\Exception\ExternalFileException
     */
    protected function getStream(Readable $input): TokenStream
    {
        return new TokenStream($this->lex($input), \PHP_INT_MAX);
    }

    /**
     * @param Readable $input
     * @return iterable|TokenInterface[]
     * @throws UnexpectedTokenException
     */
    protected function lex(Readable $input): iterable
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
            $rule = \array_pop($this->todo);

            if ($rule instanceof Escape) {
                $this->addTrace($rule);
            } else {
                $out = $this->reduce($this->rules[$rule->getName()], $rule->getState());

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
     * @param Rule $current
     * @param string|int $next
     * @return bool
     */
    private function reduce(Rule $current, $next): bool
    {
        if (! $this->stream->current()) {
            return false;
        }

        switch (true) {
            case $current instanceof Terminal:
                return $this->parseTerminal($current);

            case $current instanceof Concatenation:
                return $this->parseConcatenation($current);

            case $current instanceof Alternation:
                return $this->parseAlternation($current, $next);

            case $current instanceof Repetition:
                return $this->parseRepetition($current, $next);
        }

        return false;
    }

    /**
     * @param Terminal $token
     * @return bool
     */
    private function parseTerminal(Terminal $token): bool
    {
        /** @var TokenInterface $current */
        $current = $this->stream->current();

        if ($token->getTokenName() !== $current->getName()) {
            return false;
        }

        \array_pop($this->todo);

        $this->addTrace(new Lexeme($current, $token->isKept()));
        $this->errorToken = $this->stream->next();

        return true;
    }

    /**
     * @param Concatenation $concat
     * @return bool
     */
    private function parseConcatenation(Concatenation $concat): bool
    {
        $this->addTrace(new Entry($concat->getName()));

        $children = $concat->getChildren();

        for ($i = \count($children) - 1; $i >= 0; --$i) {
            $nextRule = $children[$i];

            $this->todo[] = new Escape($nextRule, 0);
            $this->todo[] = new Entry($nextRule, 0);
        }

        return true;
    }

    /**
     * @param Alternation $choice
     * @param string|int $next
     * @return bool
     */
    private function parseAlternation(Alternation $choice, $next): bool
    {
        $children = $choice->getChildren();

        if ($next >= \count($children)) {
            return false;
        }

        $this->addTrace(new Entry($choice->getName(), $next, $this->todo));

        $nextRule = $children[$next];

        $this->todo[] = new Escape($nextRule, 0);
        $this->todo[] = new Entry($nextRule, 0);

        return true;
    }

    /**
     * @param Repetition $repeat
     * @param string|int $next
     * @return bool
     */
    private function parseRepetition(Repetition $repeat, $next): bool
    {
        $nextRule = $repeat->getChildren();

        if ($next === 0) {
            $name = $repeat->getName();
            $min = $repeat->getMin();

            $this->addTrace(new Entry($name, $min));

            \array_pop($this->todo);

            $this->todo[] = new Escape($name, $min, $this->todo);

            for ($i = 0; $i < $min; ++$i) {
                $this->todo[] = new Escape($nextRule, 0);
                $this->todo[] = new Entry($nextRule, 0);
            }

            return true;
        }

        $max = $repeat->getMax();

        if ($max !== -1 && $next > $max) {
            return false;
        }

        $this->todo[] = new Escape($repeat->getName(), $next, $this->todo);
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
                $found = $this->rules[$last->getName()] instanceof Alternation;
            } elseif ($last instanceof Escape) {
                $found = $this->rules[$last->getName()] instanceof Repetition;
            } elseif ($last instanceof Lexeme) {
                if (! $this->stream->prev()) {
                    return false;
                }
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
}

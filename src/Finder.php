<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser;

use Railt\Io\File;
use Railt\Lexer\LexerInterface;
use Railt\Lexer\Result\Unknown;
use Railt\Lexer\TokenInterface;
use Railt\Parser\Ast\LeafInterface;
use Railt\Parser\Ast\NodeInterface;
use Railt\Parser\Ast\RuleInterface;
use Railt\Parser\Exception\UnexpectedTokenException;
use Railt\Parser\Exception\UnrecognizedTokenException;
use Railt\Parser\Finder\FinderLexer;
use Traversable;

/**
 * Class Finder
 */
class Finder implements \IteratorAggregate
{
    /**
     * @var iterable|RuleInterface[]|RuleInterface
     */
    private $node;

    /**
     * @var LexerInterface
     */
    private $lexer;

    /**
     * @var null|int
     */
    private $depth;

    /**
     * @var string
     */
    private $query = '*';

    /**
     * Finder constructor.
     * @param iterable|NodeInterface $rules
     * @throws \InvalidArgumentException
     * @throws \Railt\Lexer\Exception\BadLexemeException
     */
    public function __construct($rules)
    {
        $this->node = $rules;
        $this->lexer = new FinderLexer();
    }

    /**
     * @param iterable|NodeInterface $rules
     * @return Finder
     * @throws \InvalidArgumentException
     * @throws \Railt\Lexer\Exception\BadLexemeException
     */
    public static function new($rules): Finder
    {
        return new static($rules);
    }

    /**
     * @param string $query
     * @return Finder
     */
    public function query(string $query): Finder
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param int|null $depth
     * @return Finder
     */
    public function depth(int $depth = null): Finder
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * @param string $query
     * @return iterable|TokenInterface[]
     * @throws UnexpectedTokenException
     * @throws UnrecognizedTokenException
     */
    private function lookahead(string $query): iterable
    {
        $file = File::fromSources($query, \sprintf('"%s"', \addcslashes($query, '"')));
        $tokens = $this->lexer->lookahead($file);

        /**
         * @var TokenInterface $token
         * @var TokenInterface $next
         */
        foreach ($tokens as $token => $next) {
            if ($next->getName() === Unknown::T_NAME) {
                $error = 'Unrecognized token %s';
                $exception = new UnrecognizedTokenException(\sprintf($error, $next));
                $exception->throwsIn($file, $next->getOffset());

                throw $exception;
            }

            if ($this->lexer->isExpression($token) && $this->lexer->isExpression($next)) {
                $error = 'Unexpected token %s';
                $exception = new UnexpectedTokenException(\sprintf($error, $next));
                $exception->throwsIn($file, $next->getOffset());

                throw $exception;
            }

            yield $token => $next;
        }
    }

    /**
     * @param TokenInterface|null $token
     * @return int|null
     */
    private function exprDepth(?TokenInterface $token): ?int
    {
        if ($token === null) {
            return $this->depth;
        }

        switch ($token->getName()) {
            case FinderLexer::T_DIRECT_DEPTH:
                return 1;
            case FinderLexer::T_EXACT_DEPTH:
                return (int)$token->getValue(1);
            default:
                return null;
        }
    }

    /**
     * @param string $query
     * @return \Generator
     * @throws UnexpectedTokenException
     * @throws UnrecognizedTokenException
     */
    private function expr(string $query): \Generator
    {
        /**
         * @var TokenInterface $token
         * @var TokenInterface $lookahead
         */
        foreach ($this->lookahead($query) as $token => $lookahead) {
            switch ($lookahead->getName()) {
                case FinderLexer::T_ANY:
                    yield $this->exprDepth($token) => function (): bool {
                        return true;
                    };
                    break;

                case FinderLexer::T_NODE:
                    yield $this->exprDepth($token) => function (NodeInterface $node) use ($lookahead): bool {
                        return $lookahead->getValue(1) === $node->getName();
                    };
                    break;

                case FinderLexer::T_LEAF:
                    yield $this->exprDepth($token) => function (NodeInterface $node) use ($lookahead): bool {
                        return $lookahead->getValue(1) === $node->getName() && $node instanceof LeafInterface;
                    };
                    break;

                case FinderLexer::T_RULE:
                    yield $this->exprDepth($token) => function (NodeInterface $node) use ($lookahead): bool {
                        return $lookahead->getValue(1) === $node->getName() && $node instanceof RuleInterface;
                    };
                    break;
            }
        }
    }

    /**
     * @return iterable|NodeInterface[]
     */
    private function root(): iterable
    {
        return $this->node instanceof NodeInterface ? [$this->node] : $this->node;
    }

    /**
     * @param string $query
     * @return NodeInterface[]|RuleInterface[]|LeafInterface[]|iterable|\Generator
     * @throws UnexpectedTokenException
     * @throws UnrecognizedTokenException
     */
    public function all(string $query = null): iterable
    {
        $current = $this->root();

        foreach ($this->expr($query ?? $this->query) as $depth => $filter) {
            $depth = $depth ?? \PHP_INT_MAX;
            $current = $this->bypass($current, $filter, $depth);
        }

        yield from $current;
    }

    /**
     * @param string $query
     * @param \Closure $then
     * @return Finder
     * @throws UnexpectedTokenException
     * @throws UnrecognizedTokenException
     * @throws \InvalidArgumentException
     * @throws \Railt\Lexer\Exception\BadLexemeException
     */
    public function when(string $query, \Closure $then): Finder
    {
        return new static($this->each($query, $then));
    }

    /**
     * @param string $query
     * @param \Closure $then
     * @return iterable
     * @throws UnexpectedTokenException
     * @throws UnrecognizedTokenException
     */
    private function each(string $query, \Closure $then): iterable
    {
        foreach ($this->all($query) as $rule) {
            $result = $then($rule);

            switch (true) {
                case \is_iterable($result):
                    yield from $result;
                    break;
                case (bool)$result;
                    yield $result;
                    break;
                default:
                    yield $rule;
            }
        }
    }

    /**
     * @param string|null $query
     * @return null|NodeInterface
     * @throws UnexpectedTokenException
     * @throws UnrecognizedTokenException
     */
    public function first(string $query = null): ?NodeInterface
    {
        return $this->all($query)->current();
    }

    /**
     * @param string $query
     * @param int $group
     * @return null|string
     * @throws UnexpectedTokenException
     * @throws UnrecognizedTokenException
     */
    public function value(string $query, int $group = 0): ?string
    {
        $result = $this->first($query);

        return $result ? $result->getValue($group) : null;
    }

    /**
     * @param iterable|RuleInterface[]|RuleInterface $rule
     * @param \Closure $filter
     * @param int $depth
     * @return \Generator|RuleInterface[]|LeafInterface[]
     */
    private function bypass(iterable $rule, \Closure $filter, int $depth): \Generator
    {
        foreach ($rule as $child) {
            yield from $this->export($child, $filter, $depth);
        }
    }

    /**
     * @param NodeInterface $node
     * @param \Closure $filter
     * @param int $depth
     * @return \Generator|RuleInterface[]
     */
    private function export(NodeInterface $node, \Closure $filter, int $depth): \Generator
    {
        if ($filter($node)) {
            yield $node;
        }

        if ($depth > 0 && $node instanceof RuleInterface) {
            yield from $this->bypass($node, $filter, $depth - 1);
        }
    }

    /**
     * @return \Generator
     * @throws UnexpectedTokenException
     * @throws UnrecognizedTokenException
     */
    public function getIterator(): \Generator
    {
        yield from $this->all();
    }
}

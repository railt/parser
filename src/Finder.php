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
use Railt\Parser\Ast\Node;
use Railt\Parser\Ast\NodeInterface;
use Railt\Parser\Ast\Rule;
use Railt\Parser\Ast\RuleInterface;
use Railt\Parser\Exception\UnexpectedTokenException;
use Railt\Parser\Exception\UnrecognizedTokenException;
use Railt\Parser\Finder\Depth;
use Railt\Parser\Finder\Filter;
use Railt\Parser\Finder\FinderLexer;

/**
 * Class Finder
 */
class Finder implements \IteratorAggregate
{
    /**
     * @var NodeInterface
     */
    private $rule;

    /**
     * @var LexerInterface
     */
    private $lexer;

    /**
     * @var Depth
     */
    private $depth;

    /**
     * @var string|null
     */
    private $query;

    /**
     * Finder constructor.
     * @param NodeInterface $rule
     * @throws \Railt\Lexer\Exception\BadLexemeException
     */
    public function __construct(NodeInterface $rule)
    {
        $this->depth = Depth::any();
        $this->rule = $rule;
        $this->lexer = new FinderLexer();
    }

    /**
     * @param NodeInterface $rule
     * @return Finder
     * @throws \Railt\Lexer\Exception\BadLexemeException
     */
    public static function new(NodeInterface $rule): Finder
    {
        return new static($rule);
    }

    /**
     * @param int|null $to
     * @return Finder
     */
    public function depth(int $to = null): Finder
    {
        $this->depth->to($to);

        return $this;
    }

    /**
     * @param string $query
     * @return Finder
     */
    public function where(string $query): Finder
    {
        $this->query .= $query;

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
     * @return Depth
     */
    private function exprDepth(?TokenInterface $token): Depth
    {
        if ($token === null) {
            return $this->depth;
        }

        switch ($token->getName()) {
            case FinderLexer::T_DIRECT_DEPTH:
                return Depth::lte(1);
            case FinderLexer::T_EXACT_DEPTH:
                return Depth::equals((int)$token->getValue(1));
            default:
                return Depth::any();
        }
    }

    /**
     * @param string $query
     * @return \Generator|Filter[]
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
                    yield Filter::any($this->exprDepth($token));
                    break;

                case FinderLexer::T_NODE:
                    yield Filter::node($lookahead->getValue(1), $this->exprDepth($token));
                    break;

                case FinderLexer::T_LEAF:
                    yield Filter::leaf($lookahead->getValue(1), $this->exprDepth($token));
                    break;

                case FinderLexer::T_RULE:
                    yield Filter::rule($lookahead->getValue(1), $this->exprDepth($token));
                    break;
            }
        }
    }

    /**
     * @return string
     */
    private function query(): string
    {
        return $this->query ?? '*';
    }

    /**
     * @return NodeInterface[]|RuleInterface[]|LeafInterface[]|iterable|\Generator
     * @throws UnexpectedTokenException
     * @throws UnrecognizedTokenException
     */
    public function all(): iterable
    {
        [$expressions, $result] = [$this->expr($this->query()), [$this->rule]];

        foreach ($expressions as $expression) {
            $result = $this->exportEach($result, $expression, 0);
            $result = $this->unpack($result);
        }

        return $result;
    }

    /**
     * @param iterable $result
     * @return iterable
     */
    private function unpack(iterable $result): iterable
    {
        foreach ($result as $child) {
            if ($child instanceof RuleInterface) {
                yield from $child;
            } else {
                yield $child;
            }
        }
    }

    /**
     * @param NodeInterface $node
     * @param Filter $filter
     * @param int $depth
     * @return iterable|NodeInterface[]
     */
    private function export(NodeInterface $node, Filter $filter, int $depth): iterable
    {
        if ($this->match($node, $filter, $depth)) {
            yield $node;
        }

        if ($node instanceof RuleInterface && $filter->depth->notFinished($depth)) {
            yield from $this->bypass($node, $filter, $depth + 1);
        }
    }

    /**
     * @param iterable $nodes
     * @param Filter $filter
     * @param int $depth
     * @return iterable
     */
    private function exportEach(iterable $nodes, Filter $filter, int $depth): iterable
    {
        foreach ($nodes as $node) {
            yield from $this->export($node, $filter, $depth);
        }
    }

    /**
     * @param RuleInterface $rule
     * @param Filter $filter
     * @param int $depth
     * @return iterable|NodeInterface[]
     */
    private function bypass(RuleInterface $rule, Filter $filter, int $depth): iterable
    {
        foreach ($rule->getChildren() as $child) {
            yield from $this->export($child, $filter, $depth);
        }
    }

    /**
     * @param NodeInterface $node
     * @param Filter $filter
     * @param int $depth
     * @return bool
     */
    private function match(NodeInterface $node, Filter $filter, int $depth): bool
    {
        return $filter->match($node, $depth);
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
        foreach ($this->where($query)->all() as $rule) {
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
     * @return null|NodeInterface
     * @throws UnexpectedTokenException
     * @throws UnrecognizedTokenException
     */
    public function first(): ?NodeInterface
    {
        return $this->all()->current();
    }

    /**
     * @param int $group
     * @return null|string
     * @throws UnexpectedTokenException
     * @throws UnrecognizedTokenException
     */
    public function value(int $group = 0): ?string
    {
        $result = $this->first();

        return $result ? $result->getValue($group) : null;
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

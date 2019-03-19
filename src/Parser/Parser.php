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
use Railt\Parser\Ast\RuleInterface;
use Railt\Parser\Exception\ParserException;
use Railt\Parser\Exception\RuntimeException;
use Railt\Parser\Exception\UnexpectedTokenException;
use Railt\Parser\Exception\UnrecognizedTokenException;
use Railt\Parser\Runtime\GrammarInterface;
use Railt\Parser\Runtime\TokenStream;

/**
 * Class Parser
 */
class Parser implements ParserInterface
{
    /**
     * @var LexerInterface
     */
    private $lexer;

    /**
     * @var RuntimeInterface
     */
    private $runtime;

    /**
     * AbstractParser constructor.
     *
     * @param LexerInterface $lexer
     * @param GrammarInterface $grammar
     */
    public function __construct(LexerInterface $lexer, GrammarInterface $grammar)
    {
        $this->lexer = $lexer;
        $this->runtime = new Runtime($grammar);
    }

    /**
     * @param Readable $input
     * @return mixed|RuleInterface
     * @throws ParserException
     */
    public function parse(Readable $input)
    {
        try {
            $stream = new TokenStream($this->lex($input), \PHP_INT_MAX);

            return $this->runtime->parse($stream);
        } catch (RuntimeException $e) {
            $exception = $this->parserException($e);
            $exception->throwsIn($input, $e->getToken()->getOffset());

            throw $exception;
        }
    }

    /**
     * @param Readable $input
     * @return iterable|TokenInterface[]
     * @throws RuntimeException
     */
    private function lex(Readable $input): iterable
    {
        foreach ($this->lexer->lex($input) as $token) {
            if ($token->getName() === Unknown::T_NAME) {
                throw new RuntimeException($token, 1);
            }

            yield $token;
        }
    }

    /**
     * @param RuntimeException $e
     * @return ParserException
     */
    private function parserException(RuntimeException $e): ParserException
    {
        switch ($e->getCode()) {
            case 0:
                return new UnexpectedTokenException(\sprintf('Unexpected token %s', $e->getToken()));

            case 1:
                return new UnrecognizedTokenException(\sprintf('Unrecognized token %s', $e->getToken()));

            default:
                return new ParserException(\sprintf('Unrecognized parsing exception in %s', $e->getToken()));
                break;
        }
    }
}

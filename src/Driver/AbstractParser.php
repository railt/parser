<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Driver;

use Railt\Io\Readable;
use Railt\Lexer\LexerInterface;
use Railt\Parser\Ast\Builder;
use Railt\Parser\Ast\RuleInterface;
use Railt\Parser\Environment;
use Railt\Parser\GrammarInterface;
use Railt\Parser\ParserInterface;
use Railt\Parser\TokenStream\TokenStream;

/**
 * Class AbstractParser
 */
abstract class AbstractParser implements ParserInterface
{
    /**
     * @var LexerInterface
     */
    protected $lexer;

    /**
     * @var GrammarInterface
     */
    protected $grammar;

    /**
     * @var Environment
     */
    protected $env;

    /**
     * AbstractParser constructor.
     * @param LexerInterface $lexer
     * @param GrammarInterface $grammar
     * @param Environment|null $env
     */
    public function __construct(LexerInterface $lexer, GrammarInterface $grammar, Environment $env = null)
    {
        $this->lexer   = $lexer;
        $this->grammar = $grammar;
        $this->env     = $env ?? new Environment();
    }

    /**
     * @return Environment
     */
    public function env(): Environment
    {
        return $this->env;
    }

    /**
     * @return GrammarInterface
     */
    public function getGrammar(): GrammarInterface
    {
        return $this->grammar;
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
     * @return RuleInterface
     * @throws \Railt\Parser\Exception\InternalException
     */
    public function parse(Readable $input): RuleInterface
    {
        $trace = $this->trace($input);

        $builder = new Builder($trace, $this->grammar, $this->env);

        return $builder->build();
    }

    /**
     * @param Readable $input
     * @param int $size
     * @return TokenStream
     */
    protected function stream(Readable $input, int $size = 1024): TokenStream
    {
        return new TokenStream($this->lexer->lex($input), $size);
    }
}

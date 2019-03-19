<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Tests\Parser\Impl;

use Railt\Io\Readable;
use Railt\Lexer\Lexer;
use Railt\Lexer\LexerInterface;
use Railt\Lexer\TokenInterface;

/**
 * Class JsonLexer
 */
class JsonLexer implements LexerInterface
{
    /**
     * @var string[]
     */
    private const PRECOMPILED_PATTERNS = [
        TokenInterface::DEFAULT_TOKEN_STATE => '/\G(?|(?:\s)(*MARK:skip)|(?:true)(*MARK:true)|(?:false)(*MARK:false)|(?:null)(*MARK:null)|(?:"[^"\\\]*(\\\.[^"\\\]*)*")(*MARK:string)|(?:{)(*MARK:brace_)|(?:})(*MARK:_brace)|(?:\\[)(*MARK:bracket_)|(?:\\])(*MARK:_bracket)|(?::)(*MARK:colon)|(?:,)(*MARK:comma)|(?:\d+)(*MARK:number)|(?:.+?)(*MARK:T_UNKNOWN))/Ssu'
    ];

    /**
     * @var string[]
     */
    private const SKIP = [
        'skip'
    ];

    /**
     * @var Lexer
     */
    private $lexer;

    /**
     * JsonLexer constructor.
     */
    public function __construct()
    {
        $this->lexer = new Lexer(self::PRECOMPILED_PATTERNS, self::SKIP, []);
    }

    /**
     * @param Readable $input
     * @return iterable|TokenInterface[]
     * @throws \Railt\Lexer\Exception\LogicException
     * @throws \Railt\Lexer\Exception\RuntimeException
     */
    public function lex(Readable $input): iterable
    {
        return $this->lexer->lex($input);
    }
}

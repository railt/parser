<?php
/**
 * This file is part of Railt package and has been autogenerated.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://github.com/railt/parser/blob/No version set (parsed as 1.0.0)/LICENSE.md
 * @see https://github.com/phplrt/parser/blob/2.2.1/LICENSE.md
 * @see https://github.com/phplrt/lexer/blob/2.2.1/LICENSE.md
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime;

use Phplrt\Source\File;
use Phplrt\Lexer\Lexer as Runtime;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Railt\Parser\Extension\ExtensionInterface;
use Railt\Parser\Extension\ExtendableInterface;
use Railt\Parser\Exception\SyntaxErrorException;
use Phplrt\Contracts\Lexer\Exception\LexerRuntimeExceptionInterface;

/**
 * This class provides GraphQL lexer.
 *
 * @see https://en.wikipedia.org/wiki/Lexical_analysis
 * @internal This class is generated by railt/parser, specifically by Railt\Parser\Generator\Generator
 */
final class Lexer implements LexerInterface, ExtendableInterface
{
    /**
     * @var string
     */
    public const T_AND = 'T_AND';

    /**
     * @var string
     */
    public const T_OR = 'T_OR';

    /**
     * @var string
     */
    public const T_PARENTHESIS_OPEN = 'T_PARENTHESIS_OPEN';

    /**
     * @var string
     */
    public const T_PARENTHESIS_CLOSE = 'T_PARENTHESIS_CLOSE';

    /**
     * @var string
     */
    public const T_BRACKET_OPEN = 'T_BRACKET_OPEN';

    /**
     * @var string
     */
    public const T_BRACKET_CLOSE = 'T_BRACKET_CLOSE';

    /**
     * @var string
     */
    public const T_BRACE_OPEN = 'T_BRACE_OPEN';

    /**
     * @var string
     */
    public const T_BRACE_CLOSE = 'T_BRACE_CLOSE';

    /**
     * @var string
     */
    public const T_NON_NULL = 'T_NON_NULL';

    /**
     * @var string
     */
    public const T_THREE_DOTS = 'T_THREE_DOTS';

    /**
     * @var string
     */
    public const T_EQUAL = 'T_EQUAL';

    /**
     * @var string
     */
    public const T_DIRECTIVE_AT = 'T_DIRECTIVE_AT';

    /**
     * @var string
     */
    public const T_COLON = 'T_COLON';

    /**
     * @var string
     */
    public const T_COMMA = 'T_COMMA';

    /**
     * @var string
     */
    public const T_FLOAT_EXP = 'T_FLOAT_EXP';

    /**
     * @var string
     */
    public const T_FLOAT = 'T_FLOAT';

    /**
     * @var string
     */
    public const T_INT = 'T_INT';

    /**
     * @var string
     */
    public const T_TRUE = 'T_TRUE';

    /**
     * @var string
     */
    public const T_FALSE = 'T_FALSE';

    /**
     * @var string
     */
    public const T_NULL = 'T_NULL';

    /**
     * @var string
     */
    public const T_BLOCK_STRING = 'T_BLOCK_STRING';

    /**
     * @var string
     */
    public const T_STRING = 'T_STRING';

    /**
     * @var string
     */
    public const T_TYPE = 'T_TYPE';

    /**
     * @var string
     */
    public const T_ENUM = 'T_ENUM';

    /**
     * @var string
     */
    public const T_UNION = 'T_UNION';

    /**
     * @var string
     */
    public const T_INTERFACE = 'T_INTERFACE';

    /**
     * @var string
     */
    public const T_SCHEMA = 'T_SCHEMA';

    /**
     * @var string
     */
    public const T_SCALAR = 'T_SCALAR';

    /**
     * @var string
     */
    public const T_DIRECTIVE = 'T_DIRECTIVE';

    /**
     * @var string
     */
    public const T_INPUT = 'T_INPUT';

    /**
     * @var string
     */
    public const T_QUERY = 'T_QUERY';

    /**
     * @var string
     */
    public const T_MUTATION = 'T_MUTATION';

    /**
     * @var string
     */
    public const T_SUBSCRIPTION = 'T_SUBSCRIPTION';

    /**
     * @var string
     */
    public const T_FRAGMENT = 'T_FRAGMENT';

    /**
     * @var string
     */
    public const T_EXTEND = 'T_EXTEND';

    /**
     * @var string
     */
    public const T_EXTENDS = 'T_EXTENDS';

    /**
     * @var string
     */
    public const T_IMPLEMENTS = 'T_IMPLEMENTS';

    /**
     * @var string
     */
    public const T_ON = 'T_ON';

    /**
     * @var string
     */
    public const T_REPEATABLE = 'T_REPEATABLE';

    /**
     * @var string
     */
    public const T_PLUS = 'T_PLUS';

    /**
     * @var string
     */
    public const T_MINUS = 'T_MINUS';

    /**
     * @var string
     */
    public const T_DIV = 'T_DIV';

    /**
     * @var string
     */
    public const T_MUL = 'T_MUL';

    /**
     * @var string
     */
    public const T_VARIABLE = 'T_VARIABLE';

    /**
     * @var string
     */
    public const T_NAME = 'T_NAME';

    /**
     * @var string
     */
    public const T_COMMENT = 'T_COMMENT';

    /**
     * @var string
     */
    public const T_BOM = 'T_BOM';

    /**
     * @var string
     */
    public const T_HTAB = 'T_HTAB';

    /**
     * @var string
     */
    public const T_WHITESPACE = 'T_WHITESPACE';

    /**
     * @var string
     */
    public const T_LF = 'T_LF';

    /**
     * @var string
     */
    public const T_CR = 'T_CR';

    /**
     * @var string
     */
    public const T_INVISIBLE_WHITESPACES = 'T_INVISIBLE_WHITESPACES';

    /**
     * @var string
     */
    public const T_INVISIBLE = 'T_INVISIBLE';

    /**
     * A GraphQL document is comprised of several kinds of indivisible
     * lexical tokens defined here in a lexical grammar by patterns
     * of source Unicode characters.
     *
     * Tokens are later used as terminal symbols in a GraphQL Document
     * syntactic grammars.
     *
     * @see https://graphql.github.io/graphql-spec/draft/#sec-Source-Text.Lexical-Tokens
     * @var string[]
     */
    private const GRAPHQL_LEXICAL_TOKENS = [
        self::T_AND => '&',
        self::T_OR => '\\|',
        self::T_PARENTHESIS_OPEN => '\\(',
        self::T_PARENTHESIS_CLOSE => '\\)',
        self::T_BRACKET_OPEN => '\\[',
        self::T_BRACKET_CLOSE => '\\]',
        self::T_BRACE_OPEN => '{',
        self::T_BRACE_CLOSE => '}',
        self::T_NON_NULL => '!',
        self::T_THREE_DOTS => '\\.\\.\\.',
        self::T_EQUAL => '=',
        self::T_DIRECTIVE_AT => '@',
        self::T_COLON => ':',
        self::T_COMMA => ',',
        self::T_FLOAT_EXP => '\\-?(?:0|[1-9][0-9]*)(?:[eE][\\+\\-]?[0-9]+)',
        self::T_FLOAT => '\\-?(?:0|[1-9][0-9]*)(?:\\.[0-9]+)(?:[eE][\\+\\-]?[0-9]+)?',
        self::T_INT => '\\-?(?:0|[1-9][0-9]*)',
        self::T_TRUE => '(?<=\\b)true\\b',
        self::T_FALSE => '(?<=\\b)false\\b',
        self::T_NULL => '(?<=\\b)null\\b',
        self::T_BLOCK_STRING => '"""((?:\\\\"|(?!""").)*)"""',
        self::T_STRING => '"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"',
        self::T_TYPE => '(?<=\\b)type\\b',
        self::T_ENUM => '(?<=\\b)enum\\b',
        self::T_UNION => '(?<=\\b)union\\b',
        self::T_INTERFACE => '(?<=\\b)interface\\b',
        self::T_SCHEMA => '(?<=\\b)schema\\b',
        self::T_SCALAR => '(?<=\\b)scalar\\b',
        self::T_DIRECTIVE => '(?<=\\b)directive\\b',
        self::T_INPUT => '(?<=\\b)input\\b',
        self::T_QUERY => '(?<=\\b)query\\b',
        self::T_MUTATION => '(?<=\\b)mutation\\b',
        self::T_SUBSCRIPTION => '(?<=\\b)subscription\\b',
        self::T_FRAGMENT => '(?<=\\b)fragment\\b',
        self::T_EXTEND => '(?<=\\b)extend\\b',
        self::T_EXTENDS => '(?<=\\b)extends\\b',
        self::T_IMPLEMENTS => '(?<=\\b)implements\\b',
        self::T_ON => '(?<=\\b)on\\b',
        self::T_REPEATABLE => '(?<=\\b)repeatable\\b',
        self::T_PLUS => '\\+',
        self::T_MINUS => '\\-',
        self::T_DIV => '/',
        self::T_MUL => '\\*',
        self::T_VARIABLE => '\\$([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)',
        self::T_NAME => '[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*',
        self::T_COMMENT => '#[^\\n]*',
        self::T_BOM => '\\x{FEFF}',
        self::T_HTAB => '\\x09+',
        self::T_WHITESPACE => '\\x20+',
        self::T_LF => '\\x0A+',
        self::T_CR => '\\x0D+',
        self::T_INVISIBLE_WHITESPACES => '(?:\\x{000B}|\\x{000C}|\\x{0085}|\\x{00A0}|\\x{1680}|[\\x{2000}-\\x{200A}]|\\x{2028}|\\x{2029}|\\x{202F}|\\x{205F}|\\x{3000})+',
        self::T_INVISIBLE => '(?:\\x{180E}|\\x{200B}|\\x{200C}|\\x{200D}|\\x{2060})+',
    ];

    /**
     * Before and after every lexical token may be any amount of ignored tokens
     * including WhiteSpace and Comment. No ignored regions of a source document
     * are significant, however otherwise ignored source characters may appear
     * within a lexical token in a significant way, for example a StringValue
     * may contain white space characters and commas.
     *
     * @see https://graphql.github.io/graphql-spec/draft/#sec-Source-Text.Ignored-Tokens
     * @var string[]
     */
    private const GRAPHQL_IGNORED_TOKENS = [
        self::T_COMMENT,
        self::T_BOM,
        self::T_HTAB,
        self::T_WHITESPACE,
        self::T_LF,
        self::T_CR,
        self::T_INVISIBLE_WHITESPACES,
        self::T_INVISIBLE,
    ];

    /**
     * @var LexerInterface
     */
    private LexerInterface $lexer;

    /**
     * GraphQLLexer constructor.
     */
    public function __construct()
    {
        $this->lexer = new Runtime(self::GRAPHQL_LEXICAL_TOKENS, self::GRAPHQL_IGNORED_TOKENS);
    }

    /**
     * Returns a set of token objects from the passed source.
     *
     * @param string|resource|ReadableInterface $source
     * @param int $offset
     * @return iterable|\Generator|TokenInterface[]
     * @throws SyntaxErrorException
     * @throws \Throwable
     */
    public function lex($source, int $offset = 0): iterable
    {
        $source = File::new($source);

        try {
            yield from $this->lexer->lex($source, $offset);
        } catch (LexerRuntimeExceptionInterface $e) {
            throw new SyntaxErrorException($e->getMessage(), $source, $e->getToken()->getOffset());
        } catch (\Exception $e) {
            throw new SyntaxErrorException($e->getMessage(), $source, $offset);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function extend(ExtensionInterface $extension): void
    {
        foreach ($extension->tokens() as $name => $pcre) {
            $this->lexer->prepend($name, $pcre);
        }
    }
}


<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Tests\Parser\Impl;

use Railt\Lexer\Driver\NativeRegex;
use Railt\Lexer\LexerInterface;

/**
 * Class GraphQLLexerBuilder
 */
class GraphQLLexerBuilder
{
    public const T_AND = 'T_AND';
    public const T_OR = 'T_OR';
    public const T_PARENTHESIS_OPEN = 'T_PARENTHESIS_OPEN';
    public const T_PARENTHESIS_CLOSE = 'T_PARENTHESIS_CLOSE';
    public const T_BRACKET_OPEN = 'T_BRACKET_OPEN';
    public const T_BRACKET_CLOSE = 'T_BRACKET_CLOSE';
    public const T_BRACE_OPEN = 'T_BRACE_OPEN';
    public const T_BRACE_CLOSE = 'T_BRACE_CLOSE';
    public const T_NON_NULL = 'T_NON_NULL';
    public const T_THREE_DOTS = 'T_THREE_DOTS';
    public const T_EQUAL = 'T_EQUAL';
    public const T_DIRECTIVE_AT = 'T_DIRECTIVE_AT';
    public const T_COLON = 'T_COLON';
    public const T_COMMA = 'T_COMMA';
    public const T_HEX_NUMBER = 'T_HEX_NUMBER';
    public const T_BIN_NUMBER = 'T_BIN_NUMBER';
    public const T_NUMBER = 'T_NUMBER';
    public const T_TRUE = 'T_TRUE';
    public const T_FALSE = 'T_FALSE';
    public const T_NULL = 'T_NULL';
    public const T_BLOCK_STRING = 'T_BLOCK_STRING';
    public const T_STRING = 'T_STRING';
    public const T_TYPE = 'T_TYPE';
    public const T_ENUM = 'T_ENUM';
    public const T_UNION = 'T_UNION';
    public const T_INTERFACE = 'T_INTERFACE';
    public const T_SCHEMA = 'T_SCHEMA';
    public const T_SCALAR = 'T_SCALAR';
    public const T_DIRECTIVE = 'T_DIRECTIVE';
    public const T_INPUT = 'T_INPUT';
    public const T_QUERY = 'T_QUERY';
    public const T_MUTATION = 'T_MUTATION';
    public const T_SUBSCRIPTION = 'T_SUBSCRIPTION';
    public const T_FRAGMENT = 'T_FRAGMENT';
    public const T_EXTEND = 'T_EXTEND';
    public const T_EXTENDS = 'T_EXTENDS';
    public const T_IMPLEMENTS = 'T_IMPLEMENTS';
    public const T_ON = 'T_ON';
    public const T_PLUS = 'T_PLUS';
    public const T_MINUS = 'T_MINUS';
    public const T_DIV = 'T_DIV';
    public const T_MUL = 'T_MUL';
    public const T_VARIABLE = 'T_VARIABLE';
    public const T_NAME = 'T_NAME';
    public const T_COMMENT = 'T_COMMENT';
    public const T_HTAB = 'T_HTAB';
    public const T_LF = 'T_LF';
    public const T_CR = 'T_CR';
    public const T_WHITESPACE = 'T_WHITESPACE';
    public const T_UTF32BE_BOM = 'T_UTF32BE_BOM';
    public const T_UTF32LE_BOM = 'T_UTF32LE_BOM';
    public const T_UTF16BE_BOM = 'T_UTF16BE_BOM';
    public const T_UTF16LE_BOM = 'T_UTF16LE_BOM';
    public const T_UTF8_BOM = 'T_UTF8_BOM';
    public const T_UTF7_BOM = 'T_UTF7_BOM';

    /**
     * Lexical tokens list.
     *
     * @var string[]
     */
    protected const LEXER_TOKENS = [
        self::T_AND               => '&',
        self::T_OR                => '\\|',
        self::T_PARENTHESIS_OPEN  => '\\(',
        self::T_PARENTHESIS_CLOSE => '\\)',
        self::T_BRACKET_OPEN      => '\\[',
        self::T_BRACKET_CLOSE     => '\\]',
        self::T_BRACE_OPEN        => '{',
        self::T_BRACE_CLOSE       => '}',
        self::T_NON_NULL          => '!',
        self::T_THREE_DOTS        => '\\.\\.\\.',
        self::T_EQUAL             => '=',
        self::T_DIRECTIVE_AT      => '@',
        self::T_COLON             => ':',
        self::T_COMMA             => ',',
        self::T_HEX_NUMBER        => '\\-?0x([0-9a-fA-F]+)',
        self::T_BIN_NUMBER        => '\\-?0b([0-1]+)',
        self::T_NUMBER            => '\\-?(?:0|[1-9][0-9]*)(?:\\.[0-9]+)?(?:[eE][\\+\\-]?[0-9]+)?',
        self::T_TRUE              => '(?<=\\b)true\\b',
        self::T_FALSE             => '(?<=\\b)false\\b',
        self::T_NULL              => '(?<=\\b)null\\b',
        self::T_BLOCK_STRING      => '"""((?:\\\\"""|(?!""").)*)"""',
        self::T_STRING            => '"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"',
        self::T_TYPE              => '(?<=\\b)type\\b',
        self::T_ENUM              => '(?<=\\b)enum\\b',
        self::T_UNION             => '(?<=\\b)union\\b',
        self::T_INTERFACE         => '(?<=\\b)interface\\b',
        self::T_SCHEMA            => '(?<=\\b)schema\\b',
        self::T_SCALAR            => '(?<=\\b)scalar\\b',
        self::T_DIRECTIVE         => '(?<=\\b)directive\\b',
        self::T_INPUT             => '(?<=\\b)input\\b',
        self::T_QUERY             => '(?<=\\b)query\\b',
        self::T_MUTATION          => '(?<=\\b)mutation\\b',
        self::T_SUBSCRIPTION      => '(?<=\\b)subscription\\b',
        self::T_FRAGMENT          => '(?<=\\b)fragment\\b',
        self::T_EXTEND            => '(?<=\\b)extend\\b',
        self::T_EXTENDS           => '(?<=\\b)extends\\b',
        self::T_IMPLEMENTS        => '(?<=\\b)implements\\b',
        self::T_ON                => '(?<=\\b)on\\b',
        self::T_PLUS              => '\\\\+',
        self::T_MINUS             => '\\\\-',
        self::T_DIV               => '\\\\/',
        self::T_MUL               => '\\\\*',
        self::T_VARIABLE          => '\\$([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)',
        self::T_NAME              => '[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*',
        self::T_COMMENT           => '#[^\\n]*',
        self::T_HTAB              => '\\x09',
        self::T_LF                => '\\x0A',
        self::T_CR                => '\\x0D',
        self::T_WHITESPACE        => '\\x20',
        self::T_UTF32BE_BOM       => '^\\x00\\x00\\xFE\\xFF',
        self::T_UTF32LE_BOM       => '^\\xFE\\xFF\\x00\\x00',
        self::T_UTF16BE_BOM       => '^\\xFE\\xFF',
        self::T_UTF16LE_BOM       => '^\\xFF\\xFE',
        self::T_UTF8_BOM          => '^\\xEF\\xBB\\xBF',
        self::T_UTF7_BOM          => '^\\x2B\\x2F\\x76\\x38\\x2B\\x2F\\x76\\x39\\x2B\\x2F\\x76\\x2B\\x2B\\x2F\\x76\\x2F',
    ];

    /**
     * List of skipped tokens.
     *
     * @var string[]
     */
    protected const LEXER_SKIPPED_TOKENS = [
        'T_COMMENT',
        'T_HTAB',
        'T_LF',
        'T_CR',
        'T_WHITESPACE',
        'T_UTF32BE_BOM',
        'T_UTF32LE_BOM',
        'T_UTF16BE_BOM',
        'T_UTF16LE_BOM',
        'T_UTF8_BOM',
        'T_UTF7_BOM',
    ];

    /**
     * @return LexerInterface
     */
    public function getLexer(): LexerInterface
    {
        return new NativeRegex(static::LEXER_TOKENS, static::LEXER_SKIPPED_TOKENS);
    }
}

<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Tests\Parser\Impl;

use Railt\Lexer\Builder;
use Railt\Lexer\Builder\ProvidesLexer;
use Railt\Lexer\Driver\NativeRegex;
use Railt\Lexer\LexerInterface;

/**
 * Class SDLLexerBuilder
 */
class SDLLexerBuilder implements ProvidesLexer
{
    /**
     * @var string[]
     */
    private const LEXER_TOKENS = [
        'T_NON_NULL'            => '!',
        'T_VAR'                 => '\\$',
        'T_PARENTHESIS_OPEN'    => '\\(',
        'T_PARENTHESIS_CLOSE'   => '\\)',
        'T_THREE_DOTS'          => '\\.\\.\\.',
        'T_COLON'               => ':',
        'T_EQUAL'               => '=',
        'T_DIRECTIVE_AT'        => '@',
        'T_BRACKET_OPEN'        => '\\[',
        'T_BRACKET_CLOSE'       => '\\]',
        'T_BRACE_OPEN'          => '{',
        'T_BRACE_CLOSE'         => '}',
        'T_OR'                  => '\\|',
        'T_AND'                 => '\\&',
        'T_NUMBER_VALUE'        => '\\-?(0|[1-9][0-9]*)(\\.[0-9]+)?([eE][\\+\\-]?[0-9]+)?\\b',
        'T_BOOL_TRUE'           => 'true\\b',
        'T_BOOL_FALSE'          => 'false\\b',
        'T_NULL'                => 'null\\b',
        'T_MULTILINE_STRING'    => '"""(?:\\\"""|(?!""").|\s)*"""',
        'T_STRING'              => '"[^"\\\]*(\\\.[^"\\\]*)*"',
        'T_EXTENDS'             => 'extends\b',
        'T_TYPE_IMPLEMENTS'     => 'implements\b',
        'T_ON'                  => 'on\b',
        'T_TYPE'                => 'type\b',
        'T_ENUM'                => 'enum\b',
        'T_UNION'               => 'union\b',
        'T_INTERFACE'           => 'interface\b',
        'T_SCHEMA'              => 'schema\b',
        'T_SCHEMA_QUERY'        => 'query\b',
        'T_SCHEMA_MUTATION'     => 'mutation\b',
        'T_SCHEMA_SUBSCRIPTION' => 'subscription\b',
        'T_SCALAR'              => 'scalar\b',
        'T_DIRECTIVE'           => 'directive\b',
        'T_INPUT'               => 'input\b',
        'T_EXTEND'              => 'extend\b',
        'T_NAME'                => '([_A-Za-z][_0-9A-Za-z]*)',
        'T_VARIABLE'            => '(\$[_A-Za-z][_0-9A-Za-z]*)',
        'skip'                  => '(?:(?:[\xfe\xff|\x20|\x09|\x0a|\x0d]+|#[^\n]*)|,)',
    ];

    /**
     * @var string[]
     */
    private const LEXER_SKIPPED_TOKENS = [
        'skip'
    ];

    /**
     * @return LexerInterface
     */
    public function getLexer(): LexerInterface
    {
        $builder = new Builder();

        foreach (static::LEXER_TOKENS as $name => $pcre) {
            $builder->add($name, $pcre);
        }

        foreach (static::LEXER_SKIPPED_TOKENS as $name) {
            $builder->skip($name);
        }

        return $builder->build();
    }
}

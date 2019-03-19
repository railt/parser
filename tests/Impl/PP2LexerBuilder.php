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
use Railt\Lexer\LexerInterface;

/**
 * Class PP2LexerBuilder
 */
class PP2LexerBuilder implements ProvidesLexer
{
    /**
     * @var string[]
     */
    private const LEXER_TOKENS = [
        'T_PRAGMA'              => '%pragma\\h+([\\w\\.]+)\\h+([^\\s]+)',
        'T_INCLUDE'             => '%include\\h+([^\\s]+)',
        'T_TOKEN'               => '%token\\h+(\\w+)\\h+([^\\s]+)',
        'T_SKIP'                => '%skip\\h+(\\w+)\\h+([^\\s]+)',
        'T_OR'                  => '\\|',
        'T_TOKEN_SKIPPED'       => '::(\\w+)::',
        'T_TOKEN_KEPT'          => '<(\\w+)>',
        'T_TOKEN_STRING'        => '("[^"\\\\]+(\\\\.[^"\\\\]*)*"|\'[^\'\\\\]+(\\\\.[^\'\\\\]*)*\')',
        'T_INVOKE'              => '(\\w+)\\(\\)',
        'T_GROUP_OPEN'          => '\\(',
        'T_GROUP_CLOSE'         => '\\)',
        'T_REPEAT_ZERO_OR_ONE'  => '\\?',
        'T_REPEAT_ONE_OR_MORE'  => '\\+',
        'T_REPEAT_ZERO_OR_MORE' => '\\*',
        'T_REPEAT_N_TO_M'       => '{\\h*(\\d+)\\h*,\\h*(\\d+)\\h*}',
        'T_REPEAT_N_OR_MORE'    => '{\\h*(\\d+)\\h*,\\h*}',
        'T_REPEAT_ZERO_TO_M'    => '{\\h*,\\h*(\\d+)\\h*}',
        'T_REPEAT_EXACTLY_N'    => '{\\h*(\\d+)\\h*}',
        'T_KEPT_NAME'           => '#',
        'T_NAME'                => '[a-zA-Z_\\x7f-\\xff\\\\][a-zA-Z0-9_\\x7f-\\xff\\\\]*',
        'T_EQ'                  => '::=',
        'T_ALIAS'               => '\\bas\\b',
        'T_COLON'               => ':',
        'T_DELEGATE'            => '\\->',
        'T_WHITESPACE'          => '(\\xfe\\xff|\\x20|\\x09|\\x0a|\\x0d)+',
        'T_COMMENT'             => '//[^\\n]*',
        'T_BLOCK_COMMENT'       => '/\\*.*?\\*/',
    ];

    /**
     * @var string[]
     */
    private const LEXER_SKIPPED_TOKENS = [
        'T_WHITESPACE',
        'T_COMMENT',
        'T_BLOCK_COMMENT'
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

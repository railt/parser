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
use Railt\Parser\Builder;
use Railt\Parser\Builder\Definition\Alternation;
use Railt\Parser\Builder\Definition\Concatenation;
use Railt\Parser\Builder\Definition\Repetition;
use Railt\Parser\Builder\Definition\Lexeme;
use Railt\Parser\Parser;

/**
 * Class PP2GrammarParser
 */
class PP2GrammarParser extends Parser
{
    /**
     * PP2GrammarParser constructor.
     */
    public function __construct()
    {
        $builder = new Builder($this->rules(), 'Grammar');

        parent::__construct($this->getLexer(), $builder->getGrammar());
    }

    /**
     * @return array
     */
    private function rules(): array
    {
        return [
            new Repetition(0, 0, -1, '__definition', null),
            new Concatenation('Grammar', [0], 'Grammar'),
            new Alternation('__definition', ['TokenDefinition', 'PragmaDefinition', 'IncludeDefinition', 'RuleDefinition'], null),
            new Lexeme(3, 'T_TOKEN', true),
            new Concatenation(4, [3], 'TokenDefinition'),
            new Lexeme(5, 'T_SKIP', true),
            new Concatenation(6, [5], 'TokenDefinition'),
            new Alternation('TokenDefinition', [4, 6], null),
            new Lexeme(8, 'T_PRAGMA', true),
            new Concatenation('PragmaDefinition', [8], 'PragmaDefinition'),
            new Lexeme(10, 'T_INCLUDE', true),
            new Concatenation('IncludeDefinition', [10], 'IncludeDefinition'),
            new Repetition(12, 0, 1, 'ShouldKeep', null),
            new Repetition(13, 0, 1, 'Alias', null),
            new Repetition(14, 0, 1, 'RuleDelegate', null),
            new Concatenation('RuleDefinition', [12, 'RuleName', 13, 14, 'RuleProduction'], 'RuleDefinition'),
            new Lexeme(16, 'T_NAME', true),
            new Concatenation('RuleName', [16, '__ruleProductionDelimiter'], 'RuleName'),
            new Lexeme(18, 'T_ALIAS', false),
            new Lexeme(19, 'T_NAME', true),
            new Concatenation('Alias', [18, 19], 'Alias'),
            new Lexeme(21, 'T_DELEGATE', false),
            new Lexeme(22, 'T_NAME', true),
            new Concatenation('RuleDelegate', [21, 22], 'RuleDelegate'),
            new Lexeme(24, 'T_KEPT_NAME', false),
            new Concatenation('ShouldKeep', [24], 'ShouldKeep'),
            new Lexeme(26, 'T_COLON', false),
            new Lexeme(27, 'T_EQ', false),
            new Alternation('__ruleProductionDelimiter', [26, 27], null),
            new Concatenation('RuleProduction', ['__alternation'], null),
            new Alternation('__alternation', ['__concatenation', 'Alternation'], null),
            new Lexeme(31, 'T_OR', false),
            new Concatenation(32, [31, '__concatenation'], 'Alternation'),
            new Repetition(33, 1, -1, 32, null),
            new Concatenation('Alternation', ['__concatenation', 33], null),
            new Alternation('__concatenation', ['__repetition', 'Concatenation'], null),
            new Repetition(36, 1, -1, '__repetition', null),
            new Concatenation('Concatenation', ['__repetition', 36], 'Concatenation'),
            new Alternation('__repetition', ['__simple', 'Repetition'], null),
            new Concatenation('Repetition', ['__simple', 'Quantifier'], 'Repetition'),
            new Lexeme(40, 'T_GROUP_OPEN', false),
            new Lexeme(41, 'T_GROUP_CLOSE', false),
            new Concatenation(42, [40, '__alternation', 41], null),
            new Lexeme(43, 'T_TOKEN_SKIPPED', true),
            new Lexeme(44, 'T_TOKEN_KEPT', true),
            new Lexeme(45, 'T_INVOKE', true),
            new Alternation('__simple', [42, 43, 44, 45], null),
            new Lexeme(47, 'T_REPEAT_ZERO_OR_ONE', true),
            new Concatenation(48, [47], 'Quantifier'),
            new Lexeme(49, 'T_REPEAT_ONE_OR_MORE', true),
            new Concatenation(50, [49], 'Quantifier'),
            new Lexeme(51, 'T_REPEAT_ZERO_OR_MORE', true),
            new Concatenation(52, [51], 'Quantifier'),
            new Lexeme(53, 'T_REPEAT_N_TO_M', true),
            new Concatenation(54, [53], 'Quantifier'),
            new Lexeme(55, 'T_REPEAT_ZERO_OR_MORE', true),
            new Concatenation(56, [55], 'Quantifier'),
            new Lexeme(57, 'T_REPEAT_ZERO_TO_M', true),
            new Concatenation(58, [57], 'Quantifier'),
            new Lexeme(59, 'T_REPEAT_N_OR_MORE', true),
            new Concatenation(60, [59], 'Quantifier'),
            new Lexeme(61, 'T_REPEAT_EXACTLY_N', true),
            new Concatenation(62, [61], 'Quantifier'),
            new Alternation('Quantifier', [48, 50, 52, 54, 56, 58, 60, 62], null),
        ];
    }

    /**
     * @return LexerInterface
     */
    public function getLexer(): LexerInterface
    {
        return new NativeRegex([
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
        ], ['T_WHITESPACE', 'T_COMMENT', 'T_BLOCK_COMMENT']);
    }
}

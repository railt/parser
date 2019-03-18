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
use Railt\Parser\Builder\Definition\Terminal;
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
            0                           => new Repetition(0, 0, -1, '__definition', null),
            'Grammar'                   => new Concatenation('Grammar', [0], 'Grammar'),
            '__definition'              => new Alternation('__definition', ['TokenDefinition', 'PragmaDefinition', 'IncludeDefinition', 'RuleDefinition'], null),
            3                           => new Terminal(3, 'T_TOKEN', true),
            4                           => new Concatenation(4, [3], 'TokenDefinition'),
            5                           => new Terminal(5, 'T_SKIP', true),
            6                           => new Concatenation(6, [5], 'TokenDefinition'),
            'TokenDefinition'           => new Alternation('TokenDefinition', [4, 6], null),
            8                           => new Terminal(8, 'T_PRAGMA', true),
            'PragmaDefinition'          => new Concatenation('PragmaDefinition', [8], 'PragmaDefinition'),
            10                          => new Terminal(10, 'T_INCLUDE', true),
            'IncludeDefinition'         => new Concatenation('IncludeDefinition', [10], 'IncludeDefinition'),
            12                          => new Repetition(12, 0, 1, 'ShouldKeep', null),
            13                          => new Repetition(13, 0, 1, 'Alias', null),
            14                          => new Repetition(14, 0, 1, 'RuleDelegate', null),
            'RuleDefinition'            => new Concatenation('RuleDefinition', [12, 'RuleName', 13, 14, 'RuleProduction'], 'RuleDefinition'),
            16                          => new Terminal(16, 'T_NAME', true),
            'RuleName'                  => new Concatenation('RuleName', [16, '__ruleProductionDelimiter'], 'RuleName'),
            18                          => new Terminal(18, 'T_ALIAS', false),
            19                          => new Terminal(19, 'T_NAME', true),
            'Alias'                     => new Concatenation('Alias', [18, 19], 'Alias'),
            21                          => new Terminal(21, 'T_DELEGATE', false),
            22                          => new Terminal(22, 'T_NAME', true),
            'RuleDelegate'              => new Concatenation('RuleDelegate', [21, 22], 'RuleDelegate'),
            24                          => new Terminal(24, 'T_KEPT_NAME', false),
            'ShouldKeep'                => new Concatenation('ShouldKeep', [24], 'ShouldKeep'),
            26                          => new Terminal(26, 'T_COLON', false),
            27                          => new Terminal(27, 'T_EQ', false),
            '__ruleProductionDelimiter' => new Alternation('__ruleProductionDelimiter', [26, 27], null),
            'RuleProduction'            => new Concatenation('RuleProduction', ['__alternation'], null),
            '__alternation'             => new Alternation('__alternation', ['__concatenation', 'Alternation'], null),
            31                          => new Terminal(31, 'T_OR', false),
            32                          => new Concatenation(32, [31, '__concatenation'], 'Alternation'),
            33                          => new Repetition(33, 1, -1, 32, null),
            'Alternation'               => new Concatenation('Alternation', ['__concatenation', 33], null),
            '__concatenation'           => new Alternation('__concatenation', ['__repetition', 'Concatenation'], null),
            36                          => new Repetition(36, 1, -1, '__repetition', null),
            'Concatenation'             => new Concatenation('Concatenation', ['__repetition', 36], 'Concatenation'),
            '__repetition'              => new Alternation('__repetition', ['__simple', 'Repetition'], null),
            'Repetition'                => new Concatenation('Repetition', ['__simple', 'Quantifier'], 'Repetition'),
            40                          => new Terminal(40, 'T_GROUP_OPEN', false),
            41                          => new Terminal(41, 'T_GROUP_CLOSE', false),
            42                          => new Concatenation(42, [40, '__alternation', 41], null),
            43                          => new Terminal(43, 'T_TOKEN_SKIPPED', true),
            44                          => new Terminal(44, 'T_TOKEN_KEPT', true),
            45                          => new Terminal(45, 'T_INVOKE', true),
            '__simple'                  => new Alternation('__simple', [42, 43, 44, 45], null),
            47                          => new Terminal(47, 'T_REPEAT_ZERO_OR_ONE', true),
            48                          => new Concatenation(48, [47], 'Quantifier'),
            49                          => new Terminal(49, 'T_REPEAT_ONE_OR_MORE', true),
            50                          => new Concatenation(50, [49], 'Quantifier'),
            51                          => new Terminal(51, 'T_REPEAT_ZERO_OR_MORE', true),
            52                          => new Concatenation(52, [51], 'Quantifier'),
            53                          => new Terminal(53, 'T_REPEAT_N_TO_M', true),
            54                          => new Concatenation(54, [53], 'Quantifier'),
            55                          => new Terminal(55, 'T_REPEAT_ZERO_OR_MORE', true),
            56                          => new Concatenation(56, [55], 'Quantifier'),
            57                          => new Terminal(57, 'T_REPEAT_ZERO_TO_M', true),
            58                          => new Concatenation(58, [57], 'Quantifier'),
            59                          => new Terminal(59, 'T_REPEAT_N_OR_MORE', true),
            60                          => new Concatenation(60, [59], 'Quantifier'),
            61                          => new Terminal(61, 'T_REPEAT_EXACTLY_N', true),
            62                          => new Concatenation(62, [61], 'Quantifier'),
            'Quantifier'                => new Alternation('Quantifier', [48, 50, 52, 54, 56, 58, 60, 62], null),
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

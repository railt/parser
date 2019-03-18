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
use Railt\Parser\Parser;
use Railt\Parser\Builder\Definition\Alternation;
use Railt\Parser\Builder\Definition\Concatenation;
use Railt\Parser\Builder\Definition\Repetition;
use Railt\Parser\Builder\Definition\Lexeme;

/**
 * Class GraphQLParser
 */
class GraphQLParser extends Parser
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
     * BaseParser constructor.
     */
    public function __construct()
    {
        $builder = new Builder($this->rules(), 'Document');

        parent::__construct($this->createLexer(), $builder->getGrammar());
    }

    /**
     * @return LexerInterface
     */
    protected function createLexer(): LexerInterface
    {
        return new NativeRegex(static::LEXER_TOKENS, static::LEXER_SKIPPED_TOKENS);
    }

    /**
     * @return array|\Railt\Parser\Builder\Definition\Production[]
     */
    protected function rules(): array
    {
        return [
            new Concatenation(0, ['TypeSystemLanguage'], 'Document'),
            new Concatenation(1, ['ExecutableLanguage'], null),
            new Concatenation(2, [1], 'Document'),
            new Alternation('Document', [0, 2], 'Document'),
            new Concatenation('TypeName', ['__typeName'], 'Name'),
            new Concatenation('__typeName', ['__nameWithReserved'], null),
            new Concatenation('NameWithReserved', ['__nameWithReserved'], 'Name'),
            new Lexeme(7, 'T_TRUE', true),
            new Lexeme(8, 'T_FALSE', true),
            new Lexeme(9, 'T_NULL', true),
            new Alternation('__nameWithReserved', ['__nameWithoutValues', 7, 8, 9], null),
            new Concatenation('NameWithoutReserved', ['__nameWithoutReserved'], 'Name'),
            new Lexeme('__nameWithoutReserved', 'T_NAME', true),
            new Concatenation('NameWithoutValues', ['__nameWithoutValues'], 'Name'),
            new Lexeme(14, 'T_TYPE', true),
            new Lexeme(15, 'T_ENUM', true),
            new Lexeme(16, 'T_UNION', true),
            new Lexeme(17, 'T_INPUT_UNION', true),
            new Lexeme(18, 'T_INTERFACE', true),
            new Lexeme(19, 'T_SCHEMA', true),
            new Lexeme(20, 'T_SCALAR', true),
            new Lexeme(21, 'T_DIRECTIVE', true),
            new Lexeme(22, 'T_INPUT', true),
            new Lexeme(23, 'T_FRAGMENT', true),
            new Lexeme(24, 'T_EXTEND', true),
            new Lexeme(25, 'T_EXTENDS', true),
            new Lexeme(26, 'T_IMPLEMENTS', true),
            new Lexeme(27, 'T_ON', true),
            new Lexeme(28, 'T_QUERY', true),
            new Lexeme(29, 'T_MUTATION', true),
            new Lexeme(30, 'T_SUBSCRIPTION', true),
            new Alternation('__nameWithoutValues', ['__nameWithoutReserved', 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30], null),
            new Concatenation(32, ['TypeName'], null),
            new Concatenation('Name', [32], 'Name'),
            new Lexeme(34, 'T_FALSE', true),
            new Concatenation(35, [34], 'BooleanValue'),
            new Lexeme(36, 'T_TRUE', true),
            new Concatenation(37, [36], 'BooleanValue'),
            new Alternation('BooleanValue', [35, 37], 'BooleanValue'),
            new Concatenation(39, ['NameWithoutValues'], null),
            new Concatenation('EnumValue', [39], 'EnumValue'),
            new Concatenation(41, ['Type'], null),
            new Alternation('TypeHint', ['ListType', 'NonNullType', 41], null),
            new Lexeme(43, 'T_BRACKET_OPEN', false),
            new Concatenation(44, ['NonNullType'], 'ListType'),
            new Concatenation(45, ['Type'], 'ListType'),
            new Alternation(46, [44, 45], null),
            new Lexeme(47, 'T_BRACKET_CLOSE', false),
            new Concatenation('ListType', [43, 46, 47], 'ListType'),
            new Concatenation(49, ['ListType'], 'NonNullType'),
            new Concatenation(50, ['Type'], 'NonNullType'),
            new Alternation(51, [49, 50], null),
            new Lexeme(52, 'T_NON_NULL', false),
            new Concatenation('NonNullType', [51, 52], 'NonNullType'),
            new Concatenation(54, ['TypeName'], null),
            new Concatenation('Type', [54], 'Type'),
            new Lexeme(56, 'T_BRACKET_OPEN', false),
            new Repetition(57, 0, -1, '__listValue', null),
            new Lexeme(58, 'T_BRACKET_CLOSE', false),
            new Concatenation('ListValue', [56, 57, 58], 'ListValue'),
            new Lexeme(60, 'T_COMMA', false),
            new Repetition(61, 0, 1, 60, null),
            new Concatenation('__listValue', ['Value', 61], null),
            new Lexeme(63, 'T_NULL', false),
            new Concatenation('NullValue', [63], 'NullValue'),
            new Lexeme(65, 'T_NUMBER', true),
            new Concatenation(66, [65], 'NumberValue'),
            new Lexeme(67, 'T_HEX_NUMBER', true),
            new Concatenation(68, [67], 'NumberValue'),
            new Lexeme(69, 'T_BIN_NUMBER', true),
            new Concatenation(70, [69], 'NumberValue'),
            new Alternation('NumberValue', [66, 68, 70], 'NumberValue'),
            new Lexeme(72, 'T_BRACE_OPEN', false),
            new Repetition(73, 0, -1, 'ObjectField', null),
            new Lexeme(74, 'T_BRACE_CLOSE', false),
            new Concatenation('ObjectValue', [72, 73, 74], 'ObjectValue'),
            new Lexeme(76, 'T_COLON', false),
            new Lexeme(77, 'T_COMMA', false),
            new Repetition(78, 0, 1, 77, null),
            new Concatenation('ObjectField', ['NameWithReserved', 76, 'Value', 78], 'ObjectField'),
            new Lexeme(80, 'T_BLOCK_STRING', true),
            new Concatenation(81, [80], 'StringValue'),
            new Lexeme(82, 'T_STRING', true),
            new Concatenation(83, [82], 'StringValue'),
            new Alternation('StringValue', [81, 83], 'StringValue'),
            new Lexeme(85, 'T_VARIABLE', true),
            new Concatenation('Variable', [85], 'Variable'),
            new Concatenation(87, ['ObjectValue'], null),
            new Alternation('Value', ['Variable', 'NumberValue', 'StringValue', 'BooleanValue', 'NullValue', 'EnumValue', 'ListValue', 87], null),
            new Lexeme(89, 'T_EXTENDS', false),
            new Concatenation(90, ['TypeName'], null),
            new Concatenation('TypeDefinitionExtends', [89, 90], 'TypeDefinitionExtends'),
            new Lexeme(92, 'T_IMPLEMENTS', false),
            new Repetition(93, 0, 1, '__implementsDelimiter', null),
            new Concatenation(94, ['__implementsDelimiter', 'TypeName'], 'TypeDefinitionImplements'),
            new Repetition(95, 0, -1, 94, null),
            new Concatenation('TypeDefinitionImplements', [92, 93, 'TypeName', 95], 'TypeDefinitionImplements'),
            new Lexeme(97, 'T_COMMA', false),
            new Lexeme(98, 'T_AND', false),
            new Alternation('__implementsDelimiter', [97, 98], null),
            new Concatenation(100, ['StringValue'], null),
            new Concatenation('Description', [100], 'Description'),
            new Lexeme(102, 'T_EQUAL', false),
            new Concatenation(103, ['Value'], null),
            new Concatenation('DefaultValue', [102, 103], 'DefaultValue'),
            new Lexeme(105, 'T_ON', false),
            new Concatenation(106, ['TypeName'], null),
            new Concatenation('TypeCondition', [105, 106], 'TypeCondition'),
            new Lexeme(108, 'T_PARENTHESIS_OPEN', false),
            new Repetition(109, 0, -1, '__argument', null),
            new Lexeme(110, 'T_PARENTHESIS_CLOSE', false),
            new Concatenation('Arguments', [108, 109, 110], null),
            new Lexeme(112, 'T_COMMA', false),
            new Repetition(113, 0, 1, 112, null),
            new Concatenation('__argument', ['Argument', 113], null),
            new Lexeme(115, 'T_COLON', false),
            new Concatenation(116, ['Value'], null),
            new Concatenation('Argument', ['NameWithReserved', 115, 116], 'Argument'),
            new Repetition('Directives', 1, -1, 'Directive', null),
            new Lexeme(119, 'T_DIRECTIVE_AT', false),
            new Repetition(120, 0, 1, 'Arguments', null),
            new Concatenation('Directive', [119, 'TypeName', 120], 'Directive'),
            new Repetition(122, 0, 1, 'Alias', null),
            new Repetition(123, 0, 1, 'Arguments', null),
            new Repetition(124, 0, 1, 'Directives', null),
            new Repetition(125, 0, 1, 'SelectionSet', null),
            new Concatenation('Field', [122, 'NameWithReserved', 123, 124, 125], 'Field'),
            new Lexeme(127, 'T_COLON', false),
            new Concatenation('Alias', ['NameWithReserved', 127], 'Alias'),
            new Lexeme(129, 'T_THREE_DOTS', false),
            new Repetition(130, 0, 1, 'Directives', null),
            new Concatenation('FragmentSpread', [129, 'NameWithReserved', 130], 'FragmentSpread'),
            new Lexeme(132, 'T_THREE_DOTS', false),
            new Repetition(133, 0, 1, 'TypeCondition', null),
            new Repetition(134, 0, 1, 'Directives', null),
            new Concatenation(135, ['SelectionSet'], null),
            new Concatenation('InlineFragment', [132, 133, 134, 135], 'InlineFragment'),
            new Concatenation(137, ['SubscriptionOperation'], null),
            new Alternation('OperationDefinition', ['QueryOperation', 'MutationOperation', 137], null),
            new Lexeme(139, 'T_QUERY', false),
            new Repetition(140, 0, 1, 'NameWithReserved', null),
            new Concatenation(141, [139, 140], 'QueryOperation'),
            new Repetition(142, 0, 1, 141, null),
            new Concatenation(143, ['__operationDefinitionBody'], null),
            new Concatenation('QueryOperation', [142, 143], 'QueryOperation'),
            new Lexeme(145, 'T_MUTATION', false),
            new Repetition(146, 0, 1, 'NameWithReserved', null),
            new Concatenation(147, ['__operationDefinitionBody'], null),
            new Concatenation('MutationOperation', [145, 146, 147], 'MutationOperation'),
            new Lexeme(149, 'T_SUBSCRIPTION', false),
            new Repetition(150, 0, 1, 'NameWithReserved', null),
            new Concatenation(151, ['__operationDefinitionBody'], null),
            new Concatenation('SubscriptionOperation', [149, 150, 151], 'SubscriptionOperation'),
            new Repetition(153, 0, 1, 'VariableDefinitions', null),
            new Repetition(154, 0, 1, 'Directives', null),
            new Concatenation(155, ['SelectionSet'], null),
            new Concatenation('__operationDefinitionBody', [153, 154, 155], null),
            new Lexeme(157, 'T_BRACE_OPEN', false),
            new Repetition(158, 0, -1, '__selection', null),
            new Lexeme(159, 'T_BRACE_CLOSE', false),
            new Concatenation('SelectionSet', [157, 158, 159], null),
            new Lexeme(161, 'T_COMMA', false),
            new Repetition(162, 0, 1, 161, null),
            new Concatenation('__selection', ['Selection', 162], null),
            new Concatenation(164, ['FragmentSpread'], null),
            new Alternation('Selection', ['Field', 'InlineFragment', 164], null),
            new Repetition(166, 0, 1, 'Description', null),
            new Lexeme(167, 'T_EXTEND', false),
            new Concatenation(168, ['EnumDefinitionExceptDescription'], null),
            new Concatenation('EnumExtension', [166, 167, 168], 'EnumExtension'),
            new Repetition(170, 0, 1, 'Description', null),
            new Lexeme(171, 'T_EXTEND', false),
            new Concatenation(172, ['InputDefinitionExceptDescription'], null),
            new Concatenation('InputExtension', [170, 171, 172], 'InputExtension'),
            new Repetition(174, 0, 1, 'Description', null),
            new Lexeme(175, 'T_EXTEND', false),
            new Concatenation(176, ['InterfaceDefinitionExceptDescription'], null),
            new Concatenation('InterfaceExtension', [174, 175, 176], 'InterfaceExtension'),
            new Repetition(178, 0, 1, 'Description', null),
            new Lexeme(179, 'T_EXTEND', false),
            new Concatenation(180, ['ObjectDefinitionExceptDescription'], null),
            new Concatenation('ObjectExtension', [178, 179, 180], 'ObjectExtension'),
            new Repetition(182, 0, 1, 'Description', null),
            new Lexeme(183, 'T_EXTEND', false),
            new Concatenation(184, ['ScalarDefinitionExceptDescription'], null),
            new Concatenation('ScalarExtension', [182, 183, 184], 'ScalarExtension'),
            new Repetition(186, 0, 1, 'Description', null),
            new Lexeme(187, 'T_EXTEND', false),
            new Concatenation(188, ['SchemaDefinitionExceptDescription'], null),
            new Concatenation('SchemaExtension', [186, 187, 188], 'SchemaExtension'),
            new Repetition(190, 0, 1, 'Description', null),
            new Lexeme(191, 'T_EXTEND', false),
            new Concatenation(192, ['UnionDefinitionExceptDescription'], null),
            new Concatenation('UnionExtension', [190, 191, 192], 'UnionExtension'),
            new Lexeme(194, 'T_PARENTHESIS_OPEN', false),
            new Repetition(195, 0, 1, '__argumentDefinitions', null),
            new Lexeme(196, 'T_PARENTHESIS_CLOSE', false),
            new Concatenation('ArgumentDefinitions', [194, 195, 196], null),
            new Repetition('__argumentDefinitions', 1, -1, 'ArgumentDefinition', null),
            new Repetition(199, 0, 1, 'Description', null),
            new Repetition(200, 0, 1, 'DefaultValue', null),
            new Repetition(201, 0, -1, 'Directive', null),
            new Lexeme(202, 'T_COMMA', false),
            new Repetition(203, 0, 1, 202, null),
            new Concatenation('ArgumentDefinition', [199, '__argumentDefinitionBody', 200, 201, 203], 'ArgumentDefinition'),
            new Lexeme(205, 'T_COLON', false),
            new Concatenation(206, ['TypeHint'], null),
            new Concatenation('__argumentDefinitionBody', ['NameWithReserved', 205, 206], null),
            new Repetition(208, 0, 1, 'Description', null),
            new Concatenation(209, ['DirectiveDefinitionBody'], null),
            new Concatenation('DirectiveDefinition', [208, 'DirectiveDefinitionHead', 209], 'DirectiveDefinition'),
            new Lexeme(211, 'T_DIRECTIVE', false),
            new Lexeme(212, 'T_DIRECTIVE_AT', false),
            new Repetition(213, 0, 1, 'ArgumentDefinitions', null),
            new Concatenation('DirectiveDefinitionHead', [211, 212, 'TypeName', 213], null),
            new Lexeme(215, 'T_ON', false),
            new Concatenation(216, ['DirectiveLocations'], null),
            new Concatenation('DirectiveDefinitionBody', [215, 216], null),
            new Lexeme(218, 'T_OR', false),
            new Repetition(219, 0, 1, 218, null),
            new Lexeme(220, 'T_OR', false),
            new Concatenation(221, [220, 'DirectiveLocation'], null),
            new Repetition(222, 0, -1, 221, null),
            new Concatenation('DirectiveLocations', [219, 'DirectiveLocation', 222], null),
            new Concatenation(224, ['NameWithReserved'], null),
            new Concatenation('DirectiveLocation', [224], 'DirectiveLocation'),
            new Repetition(226, 0, 1, 'Description', null),
            new Concatenation(227, ['EnumDefinitionExceptDescription'], null),
            new Concatenation('EnumDefinition', [226, 227], 'EnumDefinition'),
            new Repetition(229, 0, 1, 'EnumDefinitionBody', null),
            new Concatenation('EnumDefinitionExceptDescription', ['EnumDefinitionHead', 229], null),
            new Lexeme(231, 'T_ENUM', false),
            new Repetition(232, 0, -1, 'Directive', null),
            new Concatenation('EnumDefinitionHead', [231, 'TypeName', 232], null),
            new Lexeme(234, 'T_BRACE_OPEN', false),
            new Repetition(235, 0, -1, 'EnumValueDefinition', null),
            new Lexeme(236, 'T_BRACE_CLOSE', false),
            new Concatenation('EnumDefinitionBody', [234, 235, 236], null),
            new Repetition(238, 0, 1, 'Description', null),
            new Repetition(239, 0, 1, 'DefaultValue', null),
            new Repetition(240, 0, -1, 'Directive', null),
            new Lexeme(241, 'T_COMMA', false),
            new Repetition(242, 0, 1, 241, null),
            new Concatenation('EnumValueDefinition', [238, 'NameWithoutValues', 239, 240, 242], 'EnumValueDefinition'),
            new Lexeme(244, 'T_FRAGMENT', false),
            new Repetition(245, 0, 1, 'Directives', null),
            new Concatenation(246, ['SelectionSet'], null),
            new Concatenation('FragmentDefinition', [244, 'NameWithReserved', 'TypeCondition', 245, 246], 'FragmentDefinition'),
            new Repetition(248, 0, 1, 'Description', null),
            new Concatenation(249, ['InputDefinitionExceptDescription'], null),
            new Concatenation('InputDefinition', [248, 249], 'InputDefinition'),
            new Repetition(251, 0, 1, 'InputDefinitionBody', null),
            new Concatenation('InputDefinitionExceptDescription', ['InputDefinitionHead', 251], null),
            new Lexeme(253, 'T_INPUT', false),
            new Repetition(254, 0, -1, 'Directive', null),
            new Concatenation('InputDefinitionHead', [253, 'TypeName', 254], null),
            new Lexeme(256, 'T_BRACE_OPEN', false),
            new Repetition(257, 0, -1, 'InputFieldDefinition', null),
            new Lexeme(258, 'T_BRACE_CLOSE', false),
            new Concatenation('InputDefinitionBody', [256, 257, 258], null),
            new Repetition(260, 0, 1, 'Description', null),
            new Repetition(261, 0, 1, 'DefaultValue', null),
            new Repetition(262, 0, -1, 'Directive', null),
            new Lexeme(263, 'T_COMMA', false),
            new Repetition(264, 0, 1, 263, null),
            new Concatenation('InputFieldDefinition', [260, '__inputFieldDefinitionBody', 261, 262, 264], 'InputFieldDefinition'),
            new Lexeme(266, 'T_COLON', false),
            new Concatenation(267, ['TypeHint'], null),
            new Concatenation('__inputFieldDefinitionBody', ['NameWithReserved', 266, 267], null),
            new Repetition(269, 0, 1, 'Description', null),
            new Concatenation(270, ['InterfaceDefinitionExceptDescription'], null),
            new Concatenation('InterfaceDefinition', [269, 270], 'InterfaceDefinition'),
            new Repetition(272, 0, 1, 'InterfaceDefinitionBody', null),
            new Concatenation('InterfaceDefinitionExceptDescription', ['InterfaceDefinitionHead', 272], null),
            new Lexeme(274, 'T_INTERFACE', false),
            new Repetition(275, 0, 1, 'TypeDefinitionImplements', null),
            new Repetition(276, 0, -1, 'Directive', null),
            new Concatenation('InterfaceDefinitionHead', [274, 'TypeName', 275, 276], null),
            new Lexeme(278, 'T_BRACE_OPEN', false),
            new Repetition(279, 0, -1, 'FieldDefinition', null),
            new Lexeme(280, 'T_BRACE_CLOSE', false),
            new Concatenation('InterfaceDefinitionBody', [278, 279, 280], null),
            new Repetition(282, 0, 1, 'Description', null),
            new Concatenation(283, ['ObjectDefinitionExceptDescription'], null),
            new Concatenation('ObjectDefinition', [282, 283], 'ObjectDefinition'),
            new Repetition(285, 0, 1, 'ObjectDefinitionBody', null),
            new Concatenation('ObjectDefinitionExceptDescription', ['ObjectDefinitionHead', 285], null),
            new Lexeme(287, 'T_TYPE', false),
            new Repetition(288, 0, 1, 'TypeDefinitionImplements', null),
            new Repetition(289, 0, -1, 'Directive', null),
            new Concatenation('ObjectDefinitionHead', [287, 'TypeName', 288, 289], null),
            new Lexeme(291, 'T_BRACE_OPEN', false),
            new Repetition(292, 0, -1, 'FieldDefinition', null),
            new Lexeme(293, 'T_BRACE_CLOSE', false),
            new Concatenation('ObjectDefinitionBody', [291, 292, 293], null),
            new Repetition(295, 0, 1, 'Description', null),
            new Repetition(296, 0, 1, 'ArgumentDefinitions', null),
            new Lexeme(297, 'T_COLON', false),
            new Repetition(298, 0, -1, 'Directive', null),
            new Lexeme(299, 'T_COMMA', false),
            new Repetition(300, 0, 1, 299, null),
            new Concatenation('FieldDefinition', [295, 'NameWithReserved', 296, 297, 'TypeHint', 298, 300], 'FieldDefinition'),
            new Repetition(302, 0, 1, 'Description', null),
            new Concatenation(303, ['ScalarDefinitionExceptDescription'], null),
            new Concatenation('ScalarDefinition', [302, 303], 'ScalarDefinition'),
            new Concatenation('ScalarDefinitionExceptDescription', ['ScalarDefinitionBody'], null),
            new Lexeme(306, 'T_SCALAR', false),
            new Repetition(307, 0, -1, 'Directive', null),
            new Repetition(308, 0, 1, 'ScalarDefinitionExtends', null),
            new Concatenation('ScalarDefinitionBody', [306, 'TypeName', 307, 308], null),
            new Lexeme(310, 'T_EXTENDS', false),
            new Concatenation(311, ['TypeName'], null),
            new Concatenation('ScalarDefinitionExtends', [310, 311], 'ScalarDefinitionExtends'),
            new Repetition(313, 0, 1, 'Description', null),
            new Concatenation(314, ['SchemaDefinitionExceptDescription'], null),
            new Concatenation('SchemaDefinition', [313, 314], 'SchemaDefinition'),
            new Repetition(316, 0, 1, 'SchemaDefinitionBody', null),
            new Concatenation('SchemaDefinitionExceptDescription', ['SchemaDefinitionHead', 316], null),
            new Lexeme(318, 'T_SCHEMA', false),
            new Repetition(319, 0, 1, 'TypeName', null),
            new Repetition(320, 0, -1, 'Directive', null),
            new Concatenation('SchemaDefinitionHead', [318, 319, 320], null),
            new Lexeme(322, 'T_BRACE_OPEN', false),
            new Lexeme(323, 'T_COMMA', false),
            new Repetition(324, 0, 1, 323, null),
            new Concatenation(325, ['SchemaFieldDefinition', 324], null),
            new Repetition(326, 0, -1, 325, null),
            new Lexeme(327, 'T_BRACE_CLOSE', false),
            new Concatenation('SchemaDefinitionBody', [322, 326, 327], null),
            new Repetition(329, 0, 1, 'Description', null),
            new Repetition(330, 0, -1, 'Directive', null),
            new Concatenation('SchemaFieldDefinition', [329, '__schemaFieldName', '__schemaFieldBody', 330], 'SchemaFieldDefinition'),
            new Concatenation('__schemaFieldName', ['SchemaFieldName'], 'Name'),
            new Lexeme(333, 'T_QUERY', true),
            new Lexeme(334, 'T_MUTATION', true),
            new Lexeme(335, 'T_SUBSCRIPTION', true),
            new Alternation('SchemaFieldName', [333, 334, 335], null),
            new Lexeme(337, 'T_COLON', false),
            new Concatenation(338, ['Type'], null),
            new Concatenation('__schemaFieldBody', [337, 338], null),
            new Repetition(340, 0, 1, 'Description', null),
            new Concatenation(341, ['UnionDefinitionExceptDescription'], null),
            new Concatenation('UnionDefinition', [340, 341], 'UnionDefinition'),
            new Repetition(343, 0, 1, 'UnionDefinitionBody', null),
            new Concatenation('UnionDefinitionExceptDescription', ['UnionDefinitionHead', 343], null),
            new Lexeme(345, 'T_UNION', false),
            new Repetition(346, 0, -1, 'Directive', null),
            new Concatenation('UnionDefinitionHead', [345, 'TypeName', 346], null),
            new Lexeme(348, 'T_EQUAL', false),
            new Repetition(349, 0, 1, 'UnionDefinitionTargets', null),
            new Concatenation('UnionDefinitionBody', [348, 349], null),
            new Lexeme(351, 'T_OR', false),
            new Repetition(352, 0, 1, 351, null),
            new Lexeme(353, 'T_OR', false),
            new Concatenation(354, [353, 'TypeName'], 'UnionDefinitionTargets'),
            new Repetition(355, 0, -1, 354, null),
            new Concatenation('UnionDefinitionTargets', [352, 'TypeName', 355], 'UnionDefinitionTargets'),
            new Lexeme(357, 'T_PARENTHESIS_OPEN', false),
            new Repetition(358, 0, -1, '__variableDefinition', null),
            new Lexeme(359, 'T_PARENTHESIS_CLOSE', false),
            new Concatenation('VariableDefinitions', [357, 358, 359], null),
            new Lexeme(361, 'T_COMMA', false),
            new Repetition(362, 0, 1, 361, null),
            new Concatenation('__variableDefinition', ['VariableDefinition', 362], null),
            new Lexeme(364, 'T_COLON', false),
            new Repetition(365, 0, 1, 'DefaultValue', null),
            new Concatenation('VariableDefinition', ['Variable', 364, 'TypeHint', 365], 'VariableDefinition'),
            new Repetition('ExecutableLanguage', 0, -1, 'ExecutableDefinition', null),
            new Concatenation(368, ['FragmentDefinition'], null),
            new Alternation('ExecutableDefinition', ['OperationDefinition', 368], null),
            new Repetition(370, 0, -1, 'TypeSystemHeader', null),
            new Repetition(371, 0, -1, 'TypeSystemBody', null),
            new Concatenation('TypeSystemLanguage', [370, 371], null),
            new Concatenation('TypeSystemHeader', ['Directive'], null),
            new Concatenation(374, ['TypeSystemExtension'], null),
            new Alternation('TypeSystemBody', ['TypeSystemDefinition', 374], null),
            new Concatenation(376, ['UnionExtension'], null),
            new Alternation('TypeSystemExtension', ['EnumExtension', 'InputExtension', 'InterfaceExtension', 'ObjectExtension', 'ScalarExtension', 'SchemaExtension', 376], null),
            new Concatenation(378, ['UnionDefinition'], null),
            new Alternation('TypeSystemDefinition', ['DirectiveDefinition', 'SchemaDefinition', 'EnumDefinition', 'InputDefinition', 'InterfaceDefinition', 'ObjectDefinition', 'ScalarDefinition', 378], null),
        ];
    }
}

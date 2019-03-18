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
use Railt\Parser\Runtime\Grammar;
use Railt\Parser\Parser;
use Railt\Parser\Builder\Definition\Alternation;
use Railt\Parser\Builder\Definition\Concatenation;
use Railt\Parser\Builder\Definition\Repetition;
use Railt\Parser\Builder\Definition\Terminal;

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
     * @return array|\Railt\Parser\Builder\Definition\Rule[]
     */
    protected function rules(): array
    {
        return [
            0                                      => new Concatenation(0, ['TypeSystemLanguage'], 'Document'),
            1                                      => new Concatenation(1, ['ExecutableLanguage'], null),
            2                                      => new Concatenation(2, [1], 'Document'),
            'Document'                             => (new Alternation('Document', [0, 2], null))->setDefaultId('Document'),
            'TypeName'                             => new Concatenation('TypeName', ['__typeName'], 'Name'),
            '__typeName'                           => new Concatenation('__typeName', ['__nameWithReserved'], null),
            'NameWithReserved'                     => new Concatenation('NameWithReserved', ['__nameWithReserved'], 'Name'),
            7                                      => new Terminal(7, 'T_TRUE', true),
            8                                      => new Terminal(8, 'T_FALSE', true),
            9                                      => new Terminal(9, 'T_NULL', true),
            '__nameWithReserved'                   => new Alternation('__nameWithReserved', ['__nameWithoutValues', 7, 8, 9], null),
            'NameWithoutReserved'                  => new Concatenation('NameWithoutReserved', ['__nameWithoutReserved'], 'Name'),
            '__nameWithoutReserved'                => new Terminal('__nameWithoutReserved', 'T_NAME', true),
            'NameWithoutValues'                    => new Concatenation('NameWithoutValues', ['__nameWithoutValues'], 'Name'),
            14                                     => new Terminal(14, 'T_TYPE', true),
            15                                     => new Terminal(15, 'T_ENUM', true),
            16                                     => new Terminal(16, 'T_UNION', true),
            17                                     => new Terminal(17, 'T_INPUT_UNION', true),
            18                                     => new Terminal(18, 'T_INTERFACE', true),
            19                                     => new Terminal(19, 'T_SCHEMA', true),
            20                                     => new Terminal(20, 'T_SCALAR', true),
            21                                     => new Terminal(21, 'T_DIRECTIVE', true),
            22                                     => new Terminal(22, 'T_INPUT', true),
            23                                     => new Terminal(23, 'T_FRAGMENT', true),
            24                                     => new Terminal(24, 'T_EXTEND', true),
            25                                     => new Terminal(25, 'T_EXTENDS', true),
            26                                     => new Terminal(26, 'T_IMPLEMENTS', true),
            27                                     => new Terminal(27, 'T_ON', true),
            28                                     => new Terminal(28, 'T_QUERY', true),
            29                                     => new Terminal(29, 'T_MUTATION', true),
            30                                     => new Terminal(30, 'T_SUBSCRIPTION', true),
            '__nameWithoutValues'                  => new Alternation('__nameWithoutValues', ['__nameWithoutReserved', 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30], null),
            32                                     => new Concatenation(32, ['TypeName'], null),
            'Name'                                 => (new Concatenation('Name', [32], 'Name'))->setDefaultId('Name'),
            34                                     => new Terminal(34, 'T_FALSE', true),
            35                                     => new Concatenation(35, [34], 'BooleanValue'),
            36                                     => new Terminal(36, 'T_TRUE', true),
            37                                     => new Concatenation(37, [36], 'BooleanValue'),
            'BooleanValue'                         => (new Alternation('BooleanValue', [35, 37], null))->setDefaultId('BooleanValue'),
            39                                     => new Concatenation(39, ['NameWithoutValues'], null),
            'EnumValue'                            => (new Concatenation('EnumValue', [39], 'EnumValue'))->setDefaultId('EnumValue'),
            41                                     => new Concatenation(41, ['Type'], null),
            'TypeHint'                             => new Alternation('TypeHint', ['ListType', 'NonNullType', 41], null),
            43                                     => new Terminal(43, 'T_BRACKET_OPEN', false),
            44                                     => new Concatenation(44, ['NonNullType'], 'ListType'),
            45                                     => new Concatenation(45, ['Type'], 'ListType'),
            46                                     => new Alternation(46, [44, 45], null),
            47                                     => new Terminal(47, 'T_BRACKET_CLOSE', false),
            'ListType'                             => (new Concatenation('ListType', [43, 46, 47], null))->setDefaultId('ListType'),
            49                                     => new Concatenation(49, ['ListType'], 'NonNullType'),
            50                                     => new Concatenation(50, ['Type'], 'NonNullType'),
            51                                     => new Alternation(51, [49, 50], null),
            52                                     => new Terminal(52, 'T_NON_NULL', false),
            'NonNullType'                          => (new Concatenation('NonNullType', [51, 52], null))->setDefaultId('NonNullType'),
            54                                     => new Concatenation(54, ['TypeName'], null),
            'Type'                                 => (new Concatenation('Type', [54], 'Type'))->setDefaultId('Type'),
            56                                     => new Terminal(56, 'T_BRACKET_OPEN', false),
            57                                     => new Repetition(57, 0, -1, '__listValue', null),
            58                                     => new Terminal(58, 'T_BRACKET_CLOSE', false),
            'ListValue'                            => (new Concatenation('ListValue', [56, 57, 58], 'ListValue'))->setDefaultId('ListValue'),
            60                                     => new Terminal(60, 'T_COMMA', false),
            61                                     => new Repetition(61, 0, 1, 60, null),
            '__listValue'                          => new Concatenation('__listValue', ['Value', 61], null),
            63                                     => new Terminal(63, 'T_NULL', false),
            'NullValue'                            => (new Concatenation('NullValue', [63], 'NullValue'))->setDefaultId('NullValue'),
            65                                     => new Terminal(65, 'T_NUMBER', true),
            66                                     => new Concatenation(66, [65], 'NumberValue'),
            67                                     => new Terminal(67, 'T_HEX_NUMBER', true),
            68                                     => new Concatenation(68, [67], 'NumberValue'),
            69                                     => new Terminal(69, 'T_BIN_NUMBER', true),
            70                                     => new Concatenation(70, [69], 'NumberValue'),
            'NumberValue'                          => (new Alternation('NumberValue', [66, 68, 70], null))->setDefaultId('NumberValue'),
            72                                     => new Terminal(72, 'T_BRACE_OPEN', false),
            73                                     => new Repetition(73, 0, -1, 'ObjectField', null),
            74                                     => new Terminal(74, 'T_BRACE_CLOSE', false),
            'ObjectValue'                          => (new Concatenation('ObjectValue', [72, 73, 74], 'ObjectValue'))->setDefaultId('ObjectValue'),
            76                                     => new Terminal(76, 'T_COLON', false),
            77                                     => new Terminal(77, 'T_COMMA', false),
            78                                     => new Repetition(78, 0, 1, 77, null),
            'ObjectField'                          => (new Concatenation('ObjectField', ['NameWithReserved', 76, 'Value', 78], 'ObjectField'))->setDefaultId('ObjectField'),
            80                                     => new Terminal(80, 'T_BLOCK_STRING', true),
            81                                     => new Concatenation(81, [80], 'StringValue'),
            82                                     => new Terminal(82, 'T_STRING', true),
            83                                     => new Concatenation(83, [82], 'StringValue'),
            'StringValue'                          => (new Alternation('StringValue', [81, 83], null))->setDefaultId('StringValue'),
            85                                     => new Terminal(85, 'T_VARIABLE', true),
            'Variable'                             => (new Concatenation('Variable', [85], 'Variable'))->setDefaultId('Variable'),
            87                                     => new Concatenation(87, ['ObjectValue'], null),
            'Value'                                => new Alternation('Value', ['Variable', 'NumberValue', 'StringValue', 'BooleanValue', 'NullValue', 'EnumValue', 'ListValue', 87], null),
            89                                     => new Terminal(89, 'T_EXTENDS', false),
            90                                     => new Concatenation(90, ['TypeName'], null),
            'TypeDefinitionExtends'                => (new Concatenation('TypeDefinitionExtends', [89, 90], 'TypeDefinitionExtends'))->setDefaultId('TypeDefinitionExtends'),
            92                                     => new Terminal(92, 'T_IMPLEMENTS', false),
            93                                     => new Repetition(93, 0, 1, '__implementsDelimiter', null),
            94                                     => new Concatenation(94, ['__implementsDelimiter', 'TypeName'], 'TypeDefinitionImplements'),
            95                                     => new Repetition(95, 0, -1, 94, null),
            'TypeDefinitionImplements'             => (new Concatenation('TypeDefinitionImplements', [92, 93, 'TypeName', 95], null))->setDefaultId('TypeDefinitionImplements'),
            97                                     => new Terminal(97, 'T_COMMA', false),
            98                                     => new Terminal(98, 'T_AND', false),
            '__implementsDelimiter'                => new Alternation('__implementsDelimiter', [97, 98], null),
            100                                    => new Concatenation(100, ['StringValue'], null),
            'Description'                          => (new Concatenation('Description', [100], 'Description'))->setDefaultId('Description'),
            102                                    => new Terminal(102, 'T_EQUAL', false),
            103                                    => new Concatenation(103, ['Value'], null),
            'DefaultValue'                         => (new Concatenation('DefaultValue', [102, 103], 'DefaultValue'))->setDefaultId('DefaultValue'),
            105                                    => new Terminal(105, 'T_ON', false),
            106                                    => new Concatenation(106, ['TypeName'], null),
            'TypeCondition'                        => (new Concatenation('TypeCondition', [105, 106], 'TypeCondition'))->setDefaultId('TypeCondition'),
            108                                    => new Terminal(108, 'T_PARENTHESIS_OPEN', false),
            109                                    => new Repetition(109, 0, -1, '__argument', null),
            110                                    => new Terminal(110, 'T_PARENTHESIS_CLOSE', false),
            'Arguments'                            => new Concatenation('Arguments', [108, 109, 110], null),
            112                                    => new Terminal(112, 'T_COMMA', false),
            113                                    => new Repetition(113, 0, 1, 112, null),
            '__argument'                           => new Concatenation('__argument', ['Argument', 113], null),
            115                                    => new Terminal(115, 'T_COLON', false),
            116                                    => new Concatenation(116, ['Value'], null),
            'Argument'                             => (new Concatenation('Argument', ['NameWithReserved', 115, 116], 'Argument'))->setDefaultId('Argument'),
            'Directives'                           => new Repetition('Directives', 1, -1, 'Directive', null),
            119                                    => new Terminal(119, 'T_DIRECTIVE_AT', false),
            120                                    => new Repetition(120, 0, 1, 'Arguments', null),
            'Directive'                            => (new Concatenation('Directive', [119, 'TypeName', 120], 'Directive'))->setDefaultId('Directive'),
            122                                    => new Repetition(122, 0, 1, 'Alias', null),
            123                                    => new Repetition(123, 0, 1, 'Arguments', null),
            124                                    => new Repetition(124, 0, 1, 'Directives', null),
            125                                    => new Repetition(125, 0, 1, 'SelectionSet', null),
            'Field'                                => (new Concatenation('Field', [122, 'NameWithReserved', 123, 124, 125], 'Field'))->setDefaultId('Field'),
            127                                    => new Terminal(127, 'T_COLON', false),
            'Alias'                                => (new Concatenation('Alias', ['NameWithReserved', 127], 'Alias'))->setDefaultId('Alias'),
            129                                    => new Terminal(129, 'T_THREE_DOTS', false),
            130                                    => new Repetition(130, 0, 1, 'Directives', null),
            'FragmentSpread'                       => (new Concatenation('FragmentSpread', [129, 'NameWithReserved', 130], 'FragmentSpread'))->setDefaultId('FragmentSpread'),
            132                                    => new Terminal(132, 'T_THREE_DOTS', false),
            133                                    => new Repetition(133, 0, 1, 'TypeCondition', null),
            134                                    => new Repetition(134, 0, 1, 'Directives', null),
            135                                    => new Concatenation(135, ['SelectionSet'], null),
            'InlineFragment'                       => (new Concatenation('InlineFragment', [132, 133, 134, 135], 'InlineFragment'))->setDefaultId('InlineFragment'),
            137                                    => new Concatenation(137, ['SubscriptionOperation'], null),
            'OperationDefinition'                  => new Alternation('OperationDefinition', ['QueryOperation', 'MutationOperation', 137], null),
            139                                    => new Terminal(139, 'T_QUERY', false),
            140                                    => new Repetition(140, 0, 1, 'NameWithReserved', null),
            141                                    => new Concatenation(141, [139, 140], 'QueryOperation'),
            142                                    => new Repetition(142, 0, 1, 141, null),
            143                                    => new Concatenation(143, ['__operationDefinitionBody'], null),
            'QueryOperation'                       => (new Concatenation('QueryOperation', [142, 143], null))->setDefaultId('QueryOperation'),
            145                                    => new Terminal(145, 'T_MUTATION', false),
            146                                    => new Repetition(146, 0, 1, 'NameWithReserved', null),
            147                                    => new Concatenation(147, ['__operationDefinitionBody'], null),
            'MutationOperation'                    => (new Concatenation('MutationOperation', [145, 146, 147], 'MutationOperation'))->setDefaultId('MutationOperation'),
            149                                    => new Terminal(149, 'T_SUBSCRIPTION', false),
            150                                    => new Repetition(150, 0, 1, 'NameWithReserved', null),
            151                                    => new Concatenation(151, ['__operationDefinitionBody'], null),
            'SubscriptionOperation'                => (new Concatenation('SubscriptionOperation', [149, 150, 151], 'SubscriptionOperation'))->setDefaultId('SubscriptionOperation'),
            153                                    => new Repetition(153, 0, 1, 'VariableDefinitions', null),
            154                                    => new Repetition(154, 0, 1, 'Directives', null),
            155                                    => new Concatenation(155, ['SelectionSet'], null),
            '__operationDefinitionBody'            => new Concatenation('__operationDefinitionBody', [153, 154, 155], null),
            157                                    => new Terminal(157, 'T_BRACE_OPEN', false),
            158                                    => new Repetition(158, 0, -1, '__selection', null),
            159                                    => new Terminal(159, 'T_BRACE_CLOSE', false),
            'SelectionSet'                         => new Concatenation('SelectionSet', [157, 158, 159], null),
            161                                    => new Terminal(161, 'T_COMMA', false),
            162                                    => new Repetition(162, 0, 1, 161, null),
            '__selection'                          => new Concatenation('__selection', ['Selection', 162], null),
            164                                    => new Concatenation(164, ['FragmentSpread'], null),
            'Selection'                            => new Alternation('Selection', ['Field', 'InlineFragment', 164], null),
            166                                    => new Repetition(166, 0, 1, 'Description', null),
            167                                    => new Terminal(167, 'T_EXTEND', false),
            168                                    => new Concatenation(168, ['EnumDefinitionExceptDescription'], null),
            'EnumExtension'                        => (new Concatenation('EnumExtension', [166, 167, 168], 'EnumExtension'))->setDefaultId('EnumExtension'),
            170                                    => new Repetition(170, 0, 1, 'Description', null),
            171                                    => new Terminal(171, 'T_EXTEND', false),
            172                                    => new Concatenation(172, ['InputDefinitionExceptDescription'], null),
            'InputExtension'                       => (new Concatenation('InputExtension', [170, 171, 172], 'InputExtension'))->setDefaultId('InputExtension'),
            174                                    => new Repetition(174, 0, 1, 'Description', null),
            175                                    => new Terminal(175, 'T_EXTEND', false),
            176                                    => new Concatenation(176, ['InterfaceDefinitionExceptDescription'], null),
            'InterfaceExtension'                   => (new Concatenation('InterfaceExtension', [174, 175, 176], 'InterfaceExtension'))->setDefaultId('InterfaceExtension'),
            178                                    => new Repetition(178, 0, 1, 'Description', null),
            179                                    => new Terminal(179, 'T_EXTEND', false),
            180                                    => new Concatenation(180, ['ObjectDefinitionExceptDescription'], null),
            'ObjectExtension'                      => (new Concatenation('ObjectExtension', [178, 179, 180], 'ObjectExtension'))->setDefaultId('ObjectExtension'),
            182                                    => new Repetition(182, 0, 1, 'Description', null),
            183                                    => new Terminal(183, 'T_EXTEND', false),
            184                                    => new Concatenation(184, ['ScalarDefinitionExceptDescription'], null),
            'ScalarExtension'                      => (new Concatenation('ScalarExtension', [182, 183, 184], 'ScalarExtension'))->setDefaultId('ScalarExtension'),
            186                                    => new Repetition(186, 0, 1, 'Description', null),
            187                                    => new Terminal(187, 'T_EXTEND', false),
            188                                    => new Concatenation(188, ['SchemaDefinitionExceptDescription'], null),
            'SchemaExtension'                      => (new Concatenation('SchemaExtension', [186, 187, 188], 'SchemaExtension'))->setDefaultId('SchemaExtension'),
            190                                    => new Repetition(190, 0, 1, 'Description', null),
            191                                    => new Terminal(191, 'T_EXTEND', false),
            192                                    => new Concatenation(192, ['UnionDefinitionExceptDescription'], null),
            'UnionExtension'                       => (new Concatenation('UnionExtension', [190, 191, 192], 'UnionExtension'))->setDefaultId('UnionExtension'),
            194                                    => new Terminal(194, 'T_PARENTHESIS_OPEN', false),
            195                                    => new Repetition(195, 0, 1, '__argumentDefinitions', null),
            196                                    => new Terminal(196, 'T_PARENTHESIS_CLOSE', false),
            'ArgumentDefinitions'                  => new Concatenation('ArgumentDefinitions', [194, 195, 196], null),
            '__argumentDefinitions'                => new Repetition('__argumentDefinitions', 1, -1, 'ArgumentDefinition', null),
            199                                    => new Repetition(199, 0, 1, 'Description', null),
            200                                    => new Repetition(200, 0, 1, 'DefaultValue', null),
            201                                    => new Repetition(201, 0, -1, 'Directive', null),
            202                                    => new Terminal(202, 'T_COMMA', false),
            203                                    => new Repetition(203, 0, 1, 202, null),
            'ArgumentDefinition'                   => (new Concatenation('ArgumentDefinition', [199, '__argumentDefinitionBody', 200, 201, 203], 'ArgumentDefinition'))->setDefaultId('ArgumentDefinition'),
            205                                    => new Terminal(205, 'T_COLON', false),
            206                                    => new Concatenation(206, ['TypeHint'], null),
            '__argumentDefinitionBody'             => new Concatenation('__argumentDefinitionBody', ['NameWithReserved', 205, 206], null),
            208                                    => new Repetition(208, 0, 1, 'Description', null),
            209                                    => new Concatenation(209, ['DirectiveDefinitionBody'], null),
            'DirectiveDefinition'                  => (new Concatenation('DirectiveDefinition', [208, 'DirectiveDefinitionHead', 209], 'DirectiveDefinition'))->setDefaultId('DirectiveDefinition'),
            211                                    => new Terminal(211, 'T_DIRECTIVE', false),
            212                                    => new Terminal(212, 'T_DIRECTIVE_AT', false),
            213                                    => new Repetition(213, 0, 1, 'ArgumentDefinitions', null),
            'DirectiveDefinitionHead'              => new Concatenation('DirectiveDefinitionHead', [211, 212, 'TypeName', 213], null),
            215                                    => new Terminal(215, 'T_ON', false),
            216                                    => new Concatenation(216, ['DirectiveLocations'], null),
            'DirectiveDefinitionBody'              => new Concatenation('DirectiveDefinitionBody', [215, 216], null),
            218                                    => new Terminal(218, 'T_OR', false),
            219                                    => new Repetition(219, 0, 1, 218, null),
            220                                    => new Terminal(220, 'T_OR', false),
            221                                    => new Concatenation(221, [220, 'DirectiveLocation'], null),
            222                                    => new Repetition(222, 0, -1, 221, null),
            'DirectiveLocations'                   => new Concatenation('DirectiveLocations', [219, 'DirectiveLocation', 222], null),
            224                                    => new Concatenation(224, ['NameWithReserved'], null),
            'DirectiveLocation'                    => (new Concatenation('DirectiveLocation', [224], 'DirectiveLocation'))->setDefaultId('DirectiveLocation'),
            226                                    => new Repetition(226, 0, 1, 'Description', null),
            227                                    => new Concatenation(227, ['EnumDefinitionExceptDescription'], null),
            'EnumDefinition'                       => (new Concatenation('EnumDefinition', [226, 227], 'EnumDefinition'))->setDefaultId('EnumDefinition'),
            229                                    => new Repetition(229, 0, 1, 'EnumDefinitionBody', null),
            'EnumDefinitionExceptDescription'      => new Concatenation('EnumDefinitionExceptDescription', ['EnumDefinitionHead', 229], null),
            231                                    => new Terminal(231, 'T_ENUM', false),
            232                                    => new Repetition(232, 0, -1, 'Directive', null),
            'EnumDefinitionHead'                   => new Concatenation('EnumDefinitionHead', [231, 'TypeName', 232], null),
            234                                    => new Terminal(234, 'T_BRACE_OPEN', false),
            235                                    => new Repetition(235, 0, -1, 'EnumValueDefinition', null),
            236                                    => new Terminal(236, 'T_BRACE_CLOSE', false),
            'EnumDefinitionBody'                   => new Concatenation('EnumDefinitionBody', [234, 235, 236], null),
            238                                    => new Repetition(238, 0, 1, 'Description', null),
            239                                    => new Repetition(239, 0, 1, 'DefaultValue', null),
            240                                    => new Repetition(240, 0, -1, 'Directive', null),
            241                                    => new Terminal(241, 'T_COMMA', false),
            242                                    => new Repetition(242, 0, 1, 241, null),
            'EnumValueDefinition'                  => (new Concatenation('EnumValueDefinition', [238, 'NameWithoutValues', 239, 240, 242], 'EnumValueDefinition'))->setDefaultId('EnumValueDefinition'),
            244                                    => new Terminal(244, 'T_FRAGMENT', false),
            245                                    => new Repetition(245, 0, 1, 'Directives', null),
            246                                    => new Concatenation(246, ['SelectionSet'], null),
            'FragmentDefinition'                   => (new Concatenation('FragmentDefinition', [244, 'NameWithReserved', 'TypeCondition', 245, 246], 'FragmentDefinition'))->setDefaultId('FragmentDefinition'),
            248                                    => new Repetition(248, 0, 1, 'Description', null),
            249                                    => new Concatenation(249, ['InputDefinitionExceptDescription'], null),
            'InputDefinition'                      => (new Concatenation('InputDefinition', [248, 249], 'InputDefinition'))->setDefaultId('InputDefinition'),
            251                                    => new Repetition(251, 0, 1, 'InputDefinitionBody', null),
            'InputDefinitionExceptDescription'     => new Concatenation('InputDefinitionExceptDescription', ['InputDefinitionHead', 251], null),
            253                                    => new Terminal(253, 'T_INPUT', false),
            254                                    => new Repetition(254, 0, -1, 'Directive', null),
            'InputDefinitionHead'                  => new Concatenation('InputDefinitionHead', [253, 'TypeName', 254], null),
            256                                    => new Terminal(256, 'T_BRACE_OPEN', false),
            257                                    => new Repetition(257, 0, -1, 'InputFieldDefinition', null),
            258                                    => new Terminal(258, 'T_BRACE_CLOSE', false),
            'InputDefinitionBody'                  => new Concatenation('InputDefinitionBody', [256, 257, 258], null),
            260                                    => new Repetition(260, 0, 1, 'Description', null),
            261                                    => new Repetition(261, 0, 1, 'DefaultValue', null),
            262                                    => new Repetition(262, 0, -1, 'Directive', null),
            263                                    => new Terminal(263, 'T_COMMA', false),
            264                                    => new Repetition(264, 0, 1, 263, null),
            'InputFieldDefinition'                 => (new Concatenation('InputFieldDefinition', [260, '__inputFieldDefinitionBody', 261, 262, 264], 'InputFieldDefinition'))->setDefaultId('InputFieldDefinition'),
            266                                    => new Terminal(266, 'T_COLON', false),
            267                                    => new Concatenation(267, ['TypeHint'], null),
            '__inputFieldDefinitionBody'           => new Concatenation('__inputFieldDefinitionBody', ['NameWithReserved', 266, 267], null),
            269                                    => new Repetition(269, 0, 1, 'Description', null),
            270                                    => new Concatenation(270, ['InterfaceDefinitionExceptDescription'], null),
            'InterfaceDefinition'                  => (new Concatenation('InterfaceDefinition', [269, 270], 'InterfaceDefinition'))->setDefaultId('InterfaceDefinition'),
            272                                    => new Repetition(272, 0, 1, 'InterfaceDefinitionBody', null),
            'InterfaceDefinitionExceptDescription' => new Concatenation('InterfaceDefinitionExceptDescription', ['InterfaceDefinitionHead', 272], null),
            274                                    => new Terminal(274, 'T_INTERFACE', false),
            275                                    => new Repetition(275, 0, 1, 'TypeDefinitionImplements', null),
            276                                    => new Repetition(276, 0, -1, 'Directive', null),
            'InterfaceDefinitionHead'              => new Concatenation('InterfaceDefinitionHead', [274, 'TypeName', 275, 276], null),
            278                                    => new Terminal(278, 'T_BRACE_OPEN', false),
            279                                    => new Repetition(279, 0, -1, 'FieldDefinition', null),
            280                                    => new Terminal(280, 'T_BRACE_CLOSE', false),
            'InterfaceDefinitionBody'              => new Concatenation('InterfaceDefinitionBody', [278, 279, 280], null),
            282                                    => new Repetition(282, 0, 1, 'Description', null),
            283                                    => new Concatenation(283, ['ObjectDefinitionExceptDescription'], null),
            'ObjectDefinition'                     => (new Concatenation('ObjectDefinition', [282, 283], 'ObjectDefinition'))->setDefaultId('ObjectDefinition'),
            285                                    => new Repetition(285, 0, 1, 'ObjectDefinitionBody', null),
            'ObjectDefinitionExceptDescription'    => new Concatenation('ObjectDefinitionExceptDescription', ['ObjectDefinitionHead', 285], null),
            287                                    => new Terminal(287, 'T_TYPE', false),
            288                                    => new Repetition(288, 0, 1, 'TypeDefinitionImplements', null),
            289                                    => new Repetition(289, 0, -1, 'Directive', null),
            'ObjectDefinitionHead'                 => new Concatenation('ObjectDefinitionHead', [287, 'TypeName', 288, 289], null),
            291                                    => new Terminal(291, 'T_BRACE_OPEN', false),
            292                                    => new Repetition(292, 0, -1, 'FieldDefinition', null),
            293                                    => new Terminal(293, 'T_BRACE_CLOSE', false),
            'ObjectDefinitionBody'                 => new Concatenation('ObjectDefinitionBody', [291, 292, 293], null),
            295                                    => new Repetition(295, 0, 1, 'Description', null),
            296                                    => new Repetition(296, 0, 1, 'ArgumentDefinitions', null),
            297                                    => new Terminal(297, 'T_COLON', false),
            298                                    => new Repetition(298, 0, -1, 'Directive', null),
            299                                    => new Terminal(299, 'T_COMMA', false),
            300                                    => new Repetition(300, 0, 1, 299, null),
            'FieldDefinition'                      => (new Concatenation('FieldDefinition', [295, 'NameWithReserved', 296, 297, 'TypeHint', 298, 300], 'FieldDefinition'))->setDefaultId('FieldDefinition'),
            302                                    => new Repetition(302, 0, 1, 'Description', null),
            303                                    => new Concatenation(303, ['ScalarDefinitionExceptDescription'], null),
            'ScalarDefinition'                     => (new Concatenation('ScalarDefinition', [302, 303], 'ScalarDefinition'))->setDefaultId('ScalarDefinition'),
            'ScalarDefinitionExceptDescription'    => new Concatenation('ScalarDefinitionExceptDescription', ['ScalarDefinitionBody'], null),
            306                                    => new Terminal(306, 'T_SCALAR', false),
            307                                    => new Repetition(307, 0, -1, 'Directive', null),
            308                                    => new Repetition(308, 0, 1, 'ScalarDefinitionExtends', null),
            'ScalarDefinitionBody'                 => new Concatenation('ScalarDefinitionBody', [306, 'TypeName', 307, 308], null),
            310                                    => new Terminal(310, 'T_EXTENDS', false),
            311                                    => new Concatenation(311, ['TypeName'], null),
            'ScalarDefinitionExtends'              => (new Concatenation('ScalarDefinitionExtends', [310, 311], 'ScalarDefinitionExtends'))->setDefaultId('ScalarDefinitionExtends'),
            313                                    => new Repetition(313, 0, 1, 'Description', null),
            314                                    => new Concatenation(314, ['SchemaDefinitionExceptDescription'], null),
            'SchemaDefinition'                     => (new Concatenation('SchemaDefinition', [313, 314], 'SchemaDefinition'))->setDefaultId('SchemaDefinition'),
            316                                    => new Repetition(316, 0, 1, 'SchemaDefinitionBody', null),
            'SchemaDefinitionExceptDescription'    => new Concatenation('SchemaDefinitionExceptDescription', ['SchemaDefinitionHead', 316], null),
            318                                    => new Terminal(318, 'T_SCHEMA', false),
            319                                    => new Repetition(319, 0, 1, 'TypeName', null),
            320                                    => new Repetition(320, 0, -1, 'Directive', null),
            'SchemaDefinitionHead'                 => new Concatenation('SchemaDefinitionHead', [318, 319, 320], null),
            322                                    => new Terminal(322, 'T_BRACE_OPEN', false),
            323                                    => new Terminal(323, 'T_COMMA', false),
            324                                    => new Repetition(324, 0, 1, 323, null),
            325                                    => new Concatenation(325, ['SchemaFieldDefinition', 324], null),
            326                                    => new Repetition(326, 0, -1, 325, null),
            327                                    => new Terminal(327, 'T_BRACE_CLOSE', false),
            'SchemaDefinitionBody'                 => new Concatenation('SchemaDefinitionBody', [322, 326, 327], null),
            329                                    => new Repetition(329, 0, 1, 'Description', null),
            330                                    => new Repetition(330, 0, -1, 'Directive', null),
            'SchemaFieldDefinition'                => (new Concatenation('SchemaFieldDefinition', [329, '__schemaFieldName', '__schemaFieldBody', 330], 'SchemaFieldDefinition'))->setDefaultId('SchemaFieldDefinition'),
            '__schemaFieldName'                    => new Concatenation('__schemaFieldName', ['SchemaFieldName'], 'Name'),
            333                                    => new Terminal(333, 'T_QUERY', true),
            334                                    => new Terminal(334, 'T_MUTATION', true),
            335                                    => new Terminal(335, 'T_SUBSCRIPTION', true),
            'SchemaFieldName'                      => new Alternation('SchemaFieldName', [333, 334, 335], null),
            337                                    => new Terminal(337, 'T_COLON', false),
            338                                    => new Concatenation(338, ['Type'], null),
            '__schemaFieldBody'                    => new Concatenation('__schemaFieldBody', [337, 338], null),
            340                                    => new Repetition(340, 0, 1, 'Description', null),
            341                                    => new Concatenation(341, ['UnionDefinitionExceptDescription'], null),
            'UnionDefinition'                      => (new Concatenation('UnionDefinition', [340, 341], 'UnionDefinition'))->setDefaultId('UnionDefinition'),
            343                                    => new Repetition(343, 0, 1, 'UnionDefinitionBody', null),
            'UnionDefinitionExceptDescription'     => new Concatenation('UnionDefinitionExceptDescription', ['UnionDefinitionHead', 343], null),
            345                                    => new Terminal(345, 'T_UNION', false),
            346                                    => new Repetition(346, 0, -1, 'Directive', null),
            'UnionDefinitionHead'                  => new Concatenation('UnionDefinitionHead', [345, 'TypeName', 346], null),
            348                                    => new Terminal(348, 'T_EQUAL', false),
            349                                    => new Repetition(349, 0, 1, 'UnionDefinitionTargets', null),
            'UnionDefinitionBody'                  => new Concatenation('UnionDefinitionBody', [348, 349], null),
            351                                    => new Terminal(351, 'T_OR', false),
            352                                    => new Repetition(352, 0, 1, 351, null),
            353                                    => new Terminal(353, 'T_OR', false),
            354                                    => new Concatenation(354, [353, 'TypeName'], 'UnionDefinitionTargets'),
            355                                    => new Repetition(355, 0, -1, 354, null),
            'UnionDefinitionTargets'               => (new Concatenation('UnionDefinitionTargets', [352, 'TypeName', 355], null))->setDefaultId('UnionDefinitionTargets'),
            357                                    => new Terminal(357, 'T_PARENTHESIS_OPEN', false),
            358                                    => new Repetition(358, 0, -1, '__variableDefinition', null),
            359                                    => new Terminal(359, 'T_PARENTHESIS_CLOSE', false),
            'VariableDefinitions'                  => new Concatenation('VariableDefinitions', [357, 358, 359], null),
            361                                    => new Terminal(361, 'T_COMMA', false),
            362                                    => new Repetition(362, 0, 1, 361, null),
            '__variableDefinition'                 => new Concatenation('__variableDefinition', ['VariableDefinition', 362], null),
            364                                    => new Terminal(364, 'T_COLON', false),
            365                                    => new Repetition(365, 0, 1, 'DefaultValue', null),
            'VariableDefinition'                   => (new Concatenation('VariableDefinition', ['Variable', 364, 'TypeHint', 365], 'VariableDefinition'))->setDefaultId('VariableDefinition'),
            'ExecutableLanguage'                   => new Repetition('ExecutableLanguage', 0, -1, 'ExecutableDefinition', null),
            368                                    => new Concatenation(368, ['FragmentDefinition'], null),
            'ExecutableDefinition'                 => new Alternation('ExecutableDefinition', ['OperationDefinition', 368], null),
            370                                    => new Repetition(370, 0, -1, 'TypeSystemHeader', null),
            371                                    => new Repetition(371, 0, -1, 'TypeSystemBody', null),
            'TypeSystemLanguage'                   => new Concatenation('TypeSystemLanguage', [370, 371], null),
            'TypeSystemHeader'                     => new Concatenation('TypeSystemHeader', ['Directive'], null),
            374                                    => new Concatenation(374, ['TypeSystemExtension'], null),
            'TypeSystemBody'                       => new Alternation('TypeSystemBody', ['TypeSystemDefinition', 374], null),
            376                                    => new Concatenation(376, ['UnionExtension'], null),
            'TypeSystemExtension'                  => new Alternation('TypeSystemExtension', ['EnumExtension', 'InputExtension', 'InterfaceExtension', 'ObjectExtension', 'ScalarExtension', 'SchemaExtension', 376], null),
            378                                    => new Concatenation(378, ['UnionDefinition'], null),
            'TypeSystemDefinition'                 => new Alternation('TypeSystemDefinition', ['DirectiveDefinition', 'SchemaDefinition', 'EnumDefinition', 'InputDefinition', 'InterfaceDefinition', 'ObjectDefinition', 'ScalarDefinition', 378], null),
        ];
    }
}

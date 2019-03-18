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
use Railt\Parser\Builder\Definition\Terminal;

/**
 * Class JsonParser
 */
class JsonParser extends Parser
{
    /**
     * JsonParser constructor.
     */
    public function __construct()
    {
        $builder = new Builder($this->rules(), 'value');

        parent::__construct($this->getLexer(), $builder->getGrammar());
    }

    /**
     * @return array
     */
    private function rules(): array
    {
        return [
            0        => new Terminal(0, 'true', true),
            1        => new Terminal(1, 'false', true),
            2        => new Terminal(2, 'null', true),
            'value'  => new Alternation('value', [0, 1, 2, 'string', 'object', 'array', 'number'], null),
            'string' => new Terminal('string', 'string', true),
            'number' => new Terminal('number', 'number', true),
            6        => new Terminal(6, 'brace_', false),
            7        => new Repetition(7, 0, 1, 'pair', null),
            8        => new Terminal(8, 'comma', false),
            9        => new Concatenation(9, [8, 'pair'], 'object'),
            10       => new Repetition(10, 0, -1, 9, null),
            11       => new Terminal(11, '_brace', false),
            'object' => (new Concatenation('object', [6, 7, 10, 11], null))->setDefaultId('object'),
            13       => new Terminal(13, 'colon', false),
            'pair'   => (new Concatenation('pair', ['string', 13, 'value'], 'pair'))->setDefaultId('pair'),
            15       => new Terminal(15, 'bracket_', false),
            16       => new Repetition(16, 0, 1, 'value', null),
            17       => new Terminal(17, 'comma', false),
            18       => new Concatenation(18, [17, 'value'], 'array'),
            19       => new Repetition(19, 0, -1, 18, null),
            20       => new Terminal(20, '_bracket', false),
            'array'  => (new Concatenation('array', [15, 16, 19, 20], null))->setDefaultId('array'),
        ];
    }

    /**
     * @return LexerInterface
     */
    public function getLexer(): LexerInterface
    {
        return new NativeRegex([
            'skip'     => '\s',
            'true'     => 'true',
            'false'    => 'false',
            'null'     => 'null',
            'string'   => '"[^"\\\]*(\\\.[^"\\\]*)*"',
            'brace_'   => '{',
            '_brace'   => '}',
            'bracket_' => '\[',
            '_bracket' => '\]',
            'colon'    => ':',
            'comma'    => ',',
            'number'   => '\d+',
        ], ['skip']);
    }
}

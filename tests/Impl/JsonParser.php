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
            new Terminal(0, 'true', true),
            new Terminal(1, 'false', true),
            new Terminal(2, 'null', true),
            new Alternation('value', [0, 1, 2, 'string', 'object', 'array', 'number'], null),
            new Terminal('string', 'string', true),
            new Terminal('number', 'number', true),
            new Terminal(6, 'brace_', false),
            new Repetition(7, 0, 1, 'pair', null),
            new Terminal(8, 'comma', false),
            new Concatenation(9, [8, 'pair'], 'object'),
            new Repetition(10, 0, -1, 9, null),
            new Terminal(11, '_brace', false),
            (new Concatenation('object', [6, 7, 10, 11], null))->setDefaultId('object'),
            new Terminal(13, 'colon', false),
            (new Concatenation('pair', ['string', 13, 'value'], 'pair'))->setDefaultId('pair'),
            new Terminal(15, 'bracket_', false),
            new Repetition(16, 0, 1, 'value', null),
            new Terminal(17, 'comma', false),
            new Concatenation(18, [17, 'value'], 'array'),
            new Repetition(19, 0, -1, 18, null),
            new Terminal(20, '_bracket', false),
            (new Concatenation('array', [15, 16, 19, 20], null))->setDefaultId('array'),
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

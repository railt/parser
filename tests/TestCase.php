<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Tests\Parser;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Railt\Lexer\Driver\NativeStateless;
use Railt\Lexer\LexerInterface;
use Railt\Parser\Ast\NodeInterface;
use Railt\Parser\Configuration;
use Railt\Parser\Parser;
use Railt\Parser\ParserInterface;
use Railt\Parser\Rule\Alternation;
use Railt\Parser\Rule\Concatenation;
use Railt\Parser\Rule\Token;

/**
 * Class TestCase
 */
abstract class TestCase extends BaseTestCase
{

    /**
     * [0 Concatenation] Expression:
     *      [8 Token] <T_NUMBER>
     *      [6] Operation()
     *      [7 Alternation] (
     *          [8] <T_NUMBER> |
     *          [0] Expression()
     *      )
     * [6 Alternation] Operation:
     *      [1 Token] <T_PLUS> |
     *      [2 Token] <T_MINUS> |
     *      [3 Token] <T_POW> |
     *      [4 Token] <T_DIV>
     */
    protected function simpleMathParser(): ParserInterface
    {
        return new Parser($this->simpleMathLexer(), [
            new Concatenation(0, [8, 6, 7], 'Expression'),
            new Alternation(6, [1, 2, 3, 4], 'Operation'),
            new Alternation(7, [8, 0]),
            new Token(8, 'T_NUMBER', true),
            new Token(1, 'T_PLUS', true),
            new Token(2, 'T_MINUS', true),
            new Token(3, 'T_POW', true),
            new Token(4, 'T_DIV', true),
        ], [Configuration::PRAGMA_ROOT => 'Expression']);
    }

    /**
     * @return LexerInterface
     */
    protected function simpleMathLexer(): LexerInterface
    {
        $lexer = new NativeStateless();
        $lexer->add('T_WHITESPACE', '\\s+', true);
        $lexer->add('T_NUMBER', '\\d+');
        $lexer->add('T_PLUS', '\\+');
        $lexer->add('T_MINUS', '\\-');
        $lexer->add('T_POW', '\\*');
        $lexer->add('T_DIV', '/');
        $lexer->add('T_GROUP_OPEN', '\\(');
        $lexer->add('T_GROUP_CLOSE', '\\)');

        return $lexer;
    }

    /**
     * @param string $expected
     * @param NodeInterface|null $actual
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    protected function assertAst(string $expected, ?NodeInterface $actual): void
    {
        $toArray = function(string $code): array {
            $parts = \explode("\n", \str_replace("\r", '', $code));

            return \array_map('\\trim', $parts);
        };

        $this->assertEquals($toArray($expected), $toArray((string)$actual));
    }
}

<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Tests\Parser;

use Railt\Io\File;
use Railt\Lexer\Driver\NativeStateless;
use Railt\Lexer\LexerInterface;
use Railt\Parser\Parser;
use Railt\Parser\ParserInterface;
use Railt\Parser\Rule\Alternation;
use Railt\Parser\Rule\Concatenation;
use Railt\Parser\Rule\Token;

/**
 * Class SimpleMathTestCase
 */
class SimpleMathTestCase extends TestCase
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
    private function simpleMath(): ParserInterface
    {
        return new Parser($this->lexer(), [
            new Concatenation(0, [8, 6, 7], 'Expression'),
            new Alternation(6, [1, 2, 3, 4], 'Operation'),
            new Alternation(7, [8, 0]),
            new Token(8, 'T_NUMBER', true),
            new Token(1, 'T_PLUS', true),
            new Token(2, 'T_MINUS', true),
            new Token(3, 'T_POW', true),
            new Token(4, 'T_DIV', true),
        ], [Parser::PRAGMA_ROOT => 'Expression']);
    }

    /**
     * @throws \PHPUnit\Framework\Exception
     */
    public function testSimpleExpression(): void
    {
       $parser = $this->simpleMath();

        $ast = $parser->parse(File::fromSources('2 + 2'));

        $this->assertEquals('<Ast>
  <Rule name="Expression" offset="0">
    <Leaf name="T_NUMBER" offset="0">2</Leaf>
    <Rule name="Operation" offset="2">
      <Leaf name="T_PLUS" offset="2">+</Leaf>
    </Rule>
    <Leaf name="T_NUMBER" offset="4">2</Leaf>
  </Rule>
</Ast>', (string)$ast);
    }

    /**
     * @return LexerInterface
     */
    private function lexer(): LexerInterface
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
     * @throws \PHPUnit\Framework\Exception
     */
    public function testLongExpression(): void
    {
        $parser = $this->simpleMath();

        $ast = $parser->parse(File::fromSources('2 + 2 + 10 + 1000'));

        $this->assertEquals('<Ast>
  <Rule name="Expression" offset="0">
    <Leaf name="T_NUMBER" offset="0">2</Leaf>
    <Rule name="Operation" offset="2">
      <Leaf name="T_PLUS" offset="2">+</Leaf>
    </Rule>
    <Rule name="Expression" offset="4">
      <Leaf name="T_NUMBER" offset="4">2</Leaf>
      <Rule name="Operation" offset="6">
        <Leaf name="T_PLUS" offset="6">+</Leaf>
      </Rule>
      <Rule name="Expression" offset="8">
        <Leaf name="T_NUMBER" offset="8">10</Leaf>
        <Rule name="Operation" offset="11">
          <Leaf name="T_PLUS" offset="11">+</Leaf>
        </Rule>
        <Leaf name="T_NUMBER" offset="13">1000</Leaf>
      </Rule>
    </Rule>
  </Rule>
</Ast>', (string)$ast);
    }
}

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

/**
 * Class AstLookupTestCase
 */
class AstLookupTestCase extends TestCase
{
    /**
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @return void
     */
    public function testFindNodes(): void
    {
        $parser = $this->simpleMathParser();

        $ast = $parser->parse(File::fromSources('2 + 2 - 10 + 1000'));

        $this->assertCount(3, $ast->find('Operation'));
        $this->assertCount(3, $ast->find('Expression'));
        $this->assertCount(4, $ast->find('T_NUMBER'));
        $this->assertCount(2, $ast->find('T_PLUS'));
        $this->assertCount(1, $ast->find('T_MINUS'));
    }

    /**
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testFindNodesWithDepth(): void
    {
        $parser = $this->simpleMathParser();

        $ast = $parser->parse(File::fromSources('2 + 2 - 10 + 1000'));

        $this->assertCount(0, $ast->find('Operation', 0));
        $this->assertCount(1, $ast->find('Operation', 1));
        $this->assertCount(2, $ast->find('Operation', 2));
        $this->assertCount(3, $ast->find('Operation', 3));

        $this->assertCount(1, $ast->find('Expression', 0));
        $this->assertCount(2, $ast->find('Expression', 1));
        $this->assertCount(3, $ast->find('Expression', 2));
    }

    /**
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @return void
     */
    public function testFindFirstNode(): void
    {
        $parser = $this->simpleMathParser();

        $ast = $parser->parse(File::fromSources('2 + 2 - 10 + 1000'));

        $this->assertEquals('<Ast>
  <Rule name="Operation" offset="2">
    <Leaf name="T_PLUS" offset="2">+</Leaf>
  </Rule>
</Ast>', (string)$ast->first('Operation'));

        $this->assertEquals('<Ast>
  <Rule name="Expression" offset="0">
    <Leaf name="T_NUMBER" offset="0">2</Leaf>
    <Rule name="Operation" offset="2">
      <Leaf name="T_PLUS" offset="2">+</Leaf>
    </Rule>
    <Rule name="Expression" offset="4">
      <Leaf name="T_NUMBER" offset="4">2</Leaf>
      <Rule name="Operation" offset="6">
        <Leaf name="T_MINUS" offset="6">-</Leaf>
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
</Ast>', (string)$ast->first('Expression'));

        $this->assertEquals('<Ast>
  <Leaf name="T_NUMBER" offset="0">2</Leaf>
</Ast>', (string)$ast->first('T_NUMBER'));

        $this->assertEquals('<Ast>
  <Leaf name="T_PLUS" offset="2">+</Leaf>
</Ast>', (string)$ast->first('T_PLUS'));

        $this->assertEquals('<Ast>
  <Leaf name="T_MINUS" offset="6">-</Leaf>
</Ast>', (string)$ast->first('T_MINUS'));

        $this->assertEquals('', (string)$ast->first('T_DIV'));
    }

    /**
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testFindFirstNodeWithDepth(): void
    {
        $parser = $this->simpleMathParser();

        $ast = $parser->parse(File::fromSources('2 + 2 - 10 + 1000'));

        $this->assertNull($ast->first('Operation', 0));
        $this->assertNotNull($ast->first('Operation', 1));
    }
}

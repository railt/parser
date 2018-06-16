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
 * Class SimpleMathTestCase
 */
class SimpleMathTestCase extends TestCase
{
    /**
     * @throws \PHPUnit\Framework\Exception
     */
    public function testSimpleExpression(): void
    {
        $parser = $this->simpleMathParser();

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
     * @throws \PHPUnit\Framework\Exception
     */
    public function testLongExpression(): void
    {
        $parser = $this->simpleMathParser();

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

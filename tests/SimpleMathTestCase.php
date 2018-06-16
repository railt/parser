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

        $this->assertAst('<Ast>
                <Expression offset="0">
                    <T_NUMBER offset="0">2</T_NUMBER>
                    <Operation offset="2">
                        <T_PLUS offset="2">+</T_PLUS>
                    </Operation>
                    <T_NUMBER offset="4">2</T_NUMBER>
                </Expression>
            </Ast>', $ast);
    }

    /**
     * @throws \PHPUnit\Framework\Exception
     */
    public function testLongExpression(): void
    {
        $parser = $this->simpleMathParser();

        $ast = $parser->parse(File::fromSources('2 + 2 + 10 + 1000'));

        $this->assertAst('<Ast>
            <Expression offset="0">
                <T_NUMBER offset="0">2</T_NUMBER>
                <Operation offset="2">
                    <T_PLUS offset="2">+</T_PLUS>
                </Operation>
                <Expression offset="4">
                    <T_NUMBER offset="4">2</T_NUMBER>
                    <Operation offset="6">
                        <T_PLUS offset="6">+</T_PLUS>
                    </Operation>
                    <Expression offset="8">
                        <T_NUMBER offset="8">10</T_NUMBER>
                        <Operation offset="11">
                            <T_PLUS offset="11">+</T_PLUS>
                        </Operation>
                        <T_NUMBER offset="13">1000</T_NUMBER>
                    </Expression>
                </Expression>
            </Expression>
        </Ast>', $ast);
    }
}

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

        $this->assertAst('<Ast>
            <Operation offset="2">
                <T_PLUS offset="2">+</T_PLUS>
            </Operation>
        </Ast>', $ast->first('Operation'));

        $this->assertAst('<Ast>
            <Expression offset="0">
                <T_NUMBER offset="0">2</T_NUMBER>
                <Operation offset="2">
                    <T_PLUS offset="2">+</T_PLUS>
                </Operation>
                <Expression offset="4">
                    <T_NUMBER offset="4">2</T_NUMBER>
                    <Operation offset="6">
                        <T_MINUS offset="6">-</T_MINUS>
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
        </Ast>', $ast->first('Expression'));

        $this->assertAst('<Ast>
            <T_NUMBER offset="0">2</T_NUMBER>
        </Ast>', $ast->first('T_NUMBER'));

        $this->assertAst('<Ast>
            <T_PLUS offset="2">+</T_PLUS>
        </Ast>', $ast->first('T_PLUS'));

        $this->assertAst('<Ast>
            <T_MINUS offset="6">-</T_MINUS>
        </Ast>', $ast->first('T_MINUS'));

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

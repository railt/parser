<?php
/**
 * This file is part of compiler package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Tests\Parser;

use Railt\Io\File;
use Railt\Io\Readable;
use Railt\Parser\Dumper\HoaDumper;
use Railt\Parser\ParserInterface;
use Railt\Tests\Parser\Impl\GraphQLParser;
use Railt\Tests\Parser\Impl\JsonParser;
use Railt\Tests\Parser\Impl\PP2GrammarParser;
use Railt\Tests\Parser\Impl\SDLParser;

/**
 * Class ParserTestCase
 */
class ParserTestCase extends TestCase
{
    /**
     * @return array
     * @throws \Railt\Io\Exception\NotReadableException
     */
    public function grammars(): array
    {
        $sources = [
            'PP2'      => [new PP2GrammarParser(), [__DIR__ . '/resources/pp2/*.pp2']],
            'JSON'     => [new JsonParser(), [__DIR__ . '/resources/json/*.json']],
            'SDL'      => [new SDLParser(), [__DIR__ . '/resources/sdl/*.graphqls']],
            'GraphQL'  => [new GraphQLParser(), [
                __DIR__ . '/resources/graphql/*.graphqls',
                __DIR__ . '/resources/graphql/*.graphql',
            ]],
        ];

        $result = [];

        foreach ($sources as $name => [$parser, $directories]) {
            foreach ($directories as $directory) {
                foreach (\glob($directory) as $file) {
                    $result[$name . ' < ' . \basename($file)] = [$parser, File::fromPathname($file)];
                }
            }
        }

        return $result;
    }

    /**
     * @dataProvider grammars
     * @param ParserInterface $parser
     * @param Readable $file
     * @throws \PHPUnit\Framework\Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testCompareAst(ParserInterface $parser, Readable $file): void
    {
        $ast = $parser->parse($file);

        $astString = (new HoaDumper($ast))->toString() . "\n";
        $out = $file->getPathname() . '.txt';

        if (! \is_file($out)) {
            \file_put_contents($out, $astString);
        }

        $this->assertStringEqualsFile($out, $astString);
    }
}

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
use Railt\Lexer\LexerInterface;
use Railt\Parser\Dumper\HoaDumper;
use Railt\Parser\Parser;
use Railt\Parser\Runtime\GrammarInterface;
use Railt\Tests\Parser\Impl\GraphQLGrammar;
use Railt\Tests\Parser\Impl\GraphQLGrammarBuilder;
use Railt\Tests\Parser\Impl\GraphQLLexerBuilder;
use Railt\Tests\Parser\Impl\JsonGrammar;
use Railt\Tests\Parser\Impl\JsonGrammarBuilder;
use Railt\Tests\Parser\Impl\JsonLexerBuilder;
use Railt\Tests\Parser\Impl\PP2GrammarBuilder;
use Railt\Tests\Parser\Impl\PP2LexerBuilder;
use Railt\Tests\Parser\Impl\SDLGrammarBuilder;
use Railt\Tests\Parser\Impl\SDLLexerBuilder;

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
            'PP2 (Lexer Builder + Parser Builder)'               => [
                (new PP2LexerBuilder())->getLexer(),
                (new PP2GrammarBuilder())->getGrammar(),
                __DIR__ . '/resources/pp2/*.pp2',
            ],
            'JSON (Lexer Builder + Parser Builder)'              => [
                (new JsonLexerBuilder())->getLexer(),
                (new JsonGrammarBuilder())->getGrammar(),
                __DIR__ . '/resources/json/*.json',
            ],
            'JSON (Lexer Builder + Compiled Parser)'              => [
                (new JsonLexerBuilder())->getLexer(),
                new JsonGrammar(),
                __DIR__ . '/resources/json/*.json',
            ],
            'SDL (Lexer Builder + Parser Builder)'               => [
                (new SDLLexerBuilder())->getLexer(),
                (new SDLGrammarBuilder())->getGrammar(),
                __DIR__ . '/resources/sdl/*.graphqls',
            ],
            'GraphQL + SDL (Lexer Builder + Parser Builder)'     => [
                (new GraphQLLexerBuilder())->getLexer(),
                (new GraphQLGrammarBuilder())->getGrammar(),
                __DIR__ . '/resources/graphql/*.graphqls',
            ],
            'GraphQL + Queries (Lexer Builder + Parser Builder)' => [
                (new GraphQLLexerBuilder())->getLexer(),
                (new GraphQLGrammarBuilder())->getGrammar(),
                __DIR__ . '/resources/graphql/*.graphql',
            ],
        ];

        $result = [];

        foreach ($sources as $name => [$lexer, $grammar, $directory]) {
            foreach (\glob($directory) as $file) {
                $result[$name . ' < ' . \basename($file)] = [$lexer, $grammar, File::fromPathname($file)];
            }
        }

        return $result;
    }

    /**
     * @dataProvider grammars
     * @param LexerInterface $lexer
     * @param GrammarInterface $grammar
     * @param Readable $file
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws \Railt\Parser\Exception\ParserException
     */
    public function testCompareAst(LexerInterface $lexer, GrammarInterface $grammar, Readable $file): void
    {
        $ast = (new Parser($lexer, $grammar))->parse($file);

        $astString = (new HoaDumper($ast))->toString() . "\n";
        $out = $file->getPathname() . '.txt';

        if (! \is_file($out)) {
            \file_put_contents($out, $astString);
        }

        $this->assertStringEqualsFile($out, $astString);
    }
}

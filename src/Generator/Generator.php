<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Generator;

use Twig\Environment;
use Phplrt\Source\File;
use Twig\Error\SyntaxError;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Phplrt\Compiler\Analyzer;
use Phplrt\Compiler\Compiler;
use Twig\Loader\FilesystemLoader;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Railt\Parser\Generator\Extension\VersionExtension;
use Railt\Parser\Generator\Extension\ZendGeneratorExtension;

/**
 * Class Generator
 *
 * @mixin Analyzer
 */
class Generator
{
    /**
     * @var string
     */
    private const GRAMMAR_PATHNAME = __DIR__ . '/../../resources/graphql/grammar.pp2';

    /**
     * @var string
     */
    private const TEMPLATES_DIRECTORY = __DIR__ . '/../../resources/templates';

    /**
     * @var string[]
     */
    private const GENERATOR_MAPPINGS = [
        __DIR__ . '/../Runtime/Parser.php'  => 'generateParser',
        __DIR__ . '/../Runtime/Builder.php' => 'generateBuilder',
        __DIR__ . '/../Runtime/Lexer.php'   => 'generateLexer',
    ];

    /**
     * @var Analyzer
     */
    private Analyzer $analyzer;

    /**
     * @var Environment
     */
    private Environment $twig;

    /**
     * Generator constructor.
     *
     * @throws NotFoundException
     * @throws NotReadableException
     * @throws \Throwable
     */
    public function __construct()
    {
        $this->twig = $this->bootTwig();
        $this->analyzer = $this->bootAnalyzer();
    }

    /**
     * @return Environment
     */
    private function bootTwig(): Environment
    {
        $environment = new Environment(new FilesystemLoader(self::TEMPLATES_DIRECTORY));

        $environment->addExtension(new VersionExtension());
        $environment->addExtension(new ZendGeneratorExtension());

        return $environment;
    }

    /**
     * @return Analyzer
     * @throws NotFoundException
     * @throws NotReadableException
     * @throws \Throwable
     */
    private function bootAnalyzer(): Analyzer
    {
        $compiler = new Compiler();
        $compiler->load(File::fromPathname(self::GRAMMAR_PATHNAME));

        return $compiler->getAnalyzer();
    }

    /**
     * @return void
     */
    public function generateAndSave(): void
    {
        foreach (self::GENERATOR_MAPPINGS as $pathname => $fn) {
            $result = \Closure::fromCallable([$this, $fn])->call($this);

            \file_put_contents($pathname, $result);
        }
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function generateLexer(): string
    {
        return $this->twig->render('lexer.twig', \get_object_vars($this->analyzer));
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function generateBuilder(): string
    {
        return $this->twig->render('builder.twig', \get_object_vars($this->analyzer));
    }

    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function generateParser(): string
    {
        return $this->twig->render('parser.twig', \get_object_vars($this->analyzer));
    }
}

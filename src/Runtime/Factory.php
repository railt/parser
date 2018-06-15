<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime;

use Railt\Parser\ParserInterface;
use Railt\Parser\Rule\Production;

/**
 * Class Factory
 */
class Factory
{
    /**
     * @var array|string[]
     */
    private $runtime = [
        'llk' => LlkRuntime::class,
    ];

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * Factory constructor.
     * @param ParserInterface $parser
     */
    public function __construct(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param Production $root
     * @param string $name
     * @return RuntimeInterface
     */
    public function get(Production $root, string $name): RuntimeInterface
    {
        $class = $this->getClass($name);

        return new $class($this->parser, $root);
    }


    /**
     * @param string|null $name
     * @return string
     */
    private function getClass(?string $name): string
    {
        if ($name !== null && \array_key_exists($name, $this->runtime)) {
            return $this->runtime[$name];
        }

        return \reset($this->runtime);
    }
}

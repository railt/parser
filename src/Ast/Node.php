<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Ast;

use Railt\Parser\Dumper\NodeDumperInterface;
use Railt\Parser\Dumper\XmlDumper;

/**
 * Class Node
 */
abstract class Node implements NodeInterface
{
    /**
     * @var array|\Closure[]
     */
    protected static $extensions = [];

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    protected $offset;

    /**
     * Node constructor.
     * @param string $name
     * @param int $offset
     */
    public function __construct(string $name, int $offset = 0)
    {
        $this->name   = $name;
        $this->offset = $offset;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function is(string $name): bool
    {
        return $this->name === $name;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        try {
            return $this->dump();
        } catch (\Throwable $e) {
            return $this->getName() . ': ' . $e->getMessage();
        }
    }

    /**
     * @param NodeDumperInterface|string $dumper
     * @return string
     */
    public function dump(string $dumper = XmlDumper::class): string
    {
        /** @var string|NodeDumperInterface $dumper */
        $dumper = new $dumper($this);

        return $dumper->toString();
    }

    /**
     * @param string $name
     * @param \Closure $then
     */
    public static function extend(string $name, \Closure $then): void
    {
        static::$extensions[$name] = $then;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed|null
     */
    public function __call(string $name, array $arguments = [])
    {
        $method = static::$extensions[$name] ?? null;

        return $method ? $method(...$arguments) : null;
    }
}

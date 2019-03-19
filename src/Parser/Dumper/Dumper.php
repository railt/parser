<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Dumper;

use Railt\Parser\Ast\LeafInterface;
use Railt\Parser\Ast\RuleInterface;

/**
 * Class Dumper
 */
abstract class Dumper implements NodeDumperInterface
{
    /**
     * @var iterable
     */
    protected $root;

    /**
     * Dumper constructor.
     *
     * @param $root
     */
    public function __construct($root)
    {
        $this->root = $root;
    }

    /**
     * @return string
     */
    protected function dump(): string
    {
        return $this->rule($this->root);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->dump();
    }

    /**
     * @param LeafInterface|mixed $leaf
     * @param int $depth
     * @return string
     */
    abstract protected function leaf($leaf, int $depth = 0): string;

    /**
     * @param RuleInterface|mixed $rule
     * @param int $depth
     * @return string
     */
    abstract protected function rule($rule, int $depth = 0): string;
}

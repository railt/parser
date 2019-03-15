<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Ast;

/**
 * Class Leaf
 */
class Leaf extends Node implements LeafInterface
{
    /**
     * @var string
     */
    private $value;

    /**
     * Leaf constructor.
     *
     * @param string $name
     * @param string $value
     * @param int $offset
     */
    public function __construct(string $name, string $value, int $offset)
    {
        parent::__construct($name, $offset);

        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}

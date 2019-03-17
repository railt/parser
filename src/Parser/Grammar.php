<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser;

/**
 * Class Grammar
 */
class Grammar
{
    /**
     * @var array
     */
    private $definitions;

    /**
     * @var string
     */
    private $root;

    /**
     * Grammar constructor.
     *
     * @param array $rules
     * @param string $root
     */
    public function __construct(array $rules, string $root)
    {
        $this->definitions = $rules;
        $this->root = $root;
    }

    /**
     * @return string
     */
    public function rootId(): string
    {
        return $this->root;
    }

    /**
     * @param int|string $rule
     * @return mixed
     */
    public function get($rule)
    {
        return $this->definitions[$rule];
    }
}

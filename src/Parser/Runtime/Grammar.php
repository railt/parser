<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime;

/**
 * Class Grammar
 */
class Grammar implements GrammarInterface
{
    /**
     * @var array|int[]
     */
    public $transitional = [];

    /**
     * @var int
     */
    public $root;

    /**
     * @var array|int[]
     */
    public $actions = [];

    /**
     * @var array|string[]|mixed[]
     */
    public $names = [];

    /**
     * @var array|int[]|array[]
     */
    public $goto = [];
}

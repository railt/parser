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
 * Interface RuleDelegate
 */
interface Delegate
{
    /**
     * RuleInterface constructor.
     * @param string $name
     * @param iterable $children
     * @param int $offset
     */
    public function __construct(string $name, iterable $children = [], int $offset = 0);
}

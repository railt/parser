<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Ast;

use Railt\Parser\Environment;

/**
 * Interface RuleDelegate
 */
interface Delegate
{
    /**
     * Delegate constructor.
     * @param Environment $env
     * @param string $name
     * @param array $children
     * @param int $offset
     */
    public function __construct(Environment $env, string $name, array $children = [], int $offset = 0);
}

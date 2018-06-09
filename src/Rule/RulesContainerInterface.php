<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Rule;

/**
 * Interface RulesContainerInterface
 */
interface RulesContainerInterface
{
    /**
     * @param Symbol $symbol
     * @return RulesContainerInterface
     */
    public function add(Symbol $symbol): RulesContainerInterface;

    /**
     * @param string|int $id
     * @return Symbol
     */
    public function fetch($id): Symbol;
}

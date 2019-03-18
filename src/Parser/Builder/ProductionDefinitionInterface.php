<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Builder;

/**
 * Interface ProductionDefinitionInterface
 */
interface ProductionDefinitionInterface
{
    /**
     * @return string|null
     */
    public function getAlias(): ?string;

    /**
     * @param string|null $alias
     * @return ProductionDefinitionInterface
     */
    public function as(string $alias): ProductionDefinitionInterface;
}

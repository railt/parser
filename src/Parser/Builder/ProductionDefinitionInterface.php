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
interface ProductionDefinitionInterface extends DefinitionInterface
{
    /**
     * @param string $name
     * @return ProductionDefinitionInterface|$this
     */
    public function as(string $name): ProductionDefinitionInterface;

    /**
     * @return string|null
     */
    public function getAlias(): ?string;

    /**
     * @param bool $transactional
     * @return ProductionDefinitionInterface|$this
     */
    public function transactional(bool $transactional = true): ProductionDefinitionInterface;

    /**
     * @return bool
     */
    public function isTransactional(): bool;
}

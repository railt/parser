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
 * Interface DefinitionInterface
 */
interface LexemeDefinitionInterface extends DefinitionInterface
{
    /**
     * @param bool $keep
     * @return LexemeDefinitionInterface|$this
     */
    public function keep(bool $keep = true): LexemeDefinitionInterface;

    /**
     * @return bool
     */
    public function isKept(): bool;
}

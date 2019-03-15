<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime\Trace;

/**
 * Interface StmtInterface
 */
interface StmtInterface extends TraceInterface
{
    /**
     * @return bool
     */
    public function isTransitional(): bool;

    /**
     * @return array
     */
    public function getJumps(): array;

    /**
     * @return int
     */
    public function getState(): int;
}

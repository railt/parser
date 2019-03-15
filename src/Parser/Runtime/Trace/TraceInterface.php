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
 * Interface TraceInterface
 */
interface TraceInterface
{
    /**
     * @return int
     */
    public function getOffset(): int;

    /**
     * @param int $offset
     * @return TraceInterface
     */
    public function at(int $offset): self;

    /**
     * @return mixed
     */
    public function getName();
}

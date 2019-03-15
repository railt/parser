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
 * Interface LexemeInterface
 */
interface LexemeInterface extends TraceInterface
{
    /**
     * @return string
     */
    public function getValue(): string;

    /**
     * @return bool
     */
    public function isKept(): bool;
}

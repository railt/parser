<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime;

use Railt\Lexer\TokenInterface;

/**
 * Interface StreamInterface
 */
interface StreamInterface
{
    /**
     * @return bool
     */
    public function isEoi(): bool;

    /**
     * @return int
     */
    public function offset(): int;

    /**
     * @return TokenInterface|null
     */
    public function current(): ?TokenInterface;

    /**
     * @return TokenInterface|null
     */
    public function next(): ?TokenInterface;

    /**
     * @return TokenInterface|null
     */
    public function prev(): ?TokenInterface;
}

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
 * Interface LeafInterface
 */
interface LeafInterface extends NodeInterface
{
    /**
     * @return string
     */
    public function getValue(): string;
}

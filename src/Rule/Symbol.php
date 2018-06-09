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
 * Interface Symbol
 */
interface Symbol
{
    /**
     * Rule position (index) in extended Backus–Naur form grammar symbol table.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Indicates whether the rule should be contained within the AST.
     *
     * @return bool
     */
    public function isKept(): bool;
}

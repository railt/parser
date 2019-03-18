<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime;

/**
 * Interface ProvidesGrammarInterface
 */
interface GrammarInterface
{
    /**
     * @return string
     */
    public function rootId(): string;

    /**
     * @param string|int $id
     * @return bool
     */
    public function isTerminal($id): bool;

    /**
     * @param string|int $id
     * @return bool
     */
    public function isConcatenation($id): bool;

    /**
     * @param string|int $id
     * @return bool
     */
    public function isAlternation($id): bool;

    /**
     * @param string|int $id
     * @return bool
     */
    public function isRepetition($id): bool;

    /**
     * @param string|int $id
     * @return string|null
     */
    public function getNodeId($id): ?string;

    /**
     * @param string|int $id
     * @return string|null
     */
    public function getDefaultId($id): ?string;

    /**
     * @param string|int $id
     * @return bool
     */
    public function isTransitional($id): bool;

    /**
     * @param string|int $id
     * @return bool
     */
    public function isKept($id): bool;

    /**
     * @param string|int $id
     * @return string
     */
    public function getTokenName($id): string;

    /**
     * @param string|int $id
     * @return int|int[]|string|string[]
     */
    public function getChildren($id);

    /**
     * @param string|int $id
     * @return int
     */
    public function getMin($id): int;

    /**
     * @param string|int $id
     * @return int
     */
    public function getMax($id): int;
}



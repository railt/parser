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
 *
 * @property int $root
 * @property array|int[] $transitional
 * @property array $actions
 * @property array $names
 * @property array $goto
 */
interface GrammarInterface
{
    /**
     * @var int
     */
    public const TYPE_ALTERNATION = 0x00;

    /**
     * @var int
     */
    public const TYPE_CONCATENATION = 0x01;

    /**
     * @var int
     */
    public const TYPE_REPETITION = 0x02;

    /**
     * @var int
     */
    public const TYPE_TERMINAL = 0x03;

    /**
     * @var int
     */
    public const REPEAT_MIN = 0x01;

    /**
     * @var int
     */
    public const REPEAT_MAX = 0x02;
}



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
 * Class BaseSymbol
 */
abstract class BaseSymbol implements Symbol
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var bool
     */
    protected $kept;

    /**
     * BaseSymbol constructor.
     * @param int $id
     * @param bool $kept
     */
    public function __construct(int $id, bool $kept = false)
    {
        $this->id   = $id;
        $this->kept = $kept;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isKept(): bool
    {
        return $this->kept;
    }
}

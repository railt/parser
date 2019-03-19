<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Builder\Definition;

/**
 * Class Repetition
 */
class Repetition extends Production
{
    /**
     * Minimum bound.
     *
     * @var int
     */
    protected $min = 0;

    /**
     * Maximum bound.
     *
     * @var int
     */
    protected $max = 0;

    /**
     * Repetition constructor.
     *
     * @param string|int $name Rule name.
     * @param int $min Minimum bound.
     * @param int $max Maximum bound.
     * @param string|int $then Children.
     * @param string|null $alias Node ID.
     */
    public function __construct($name, $min, $max, $then, string $alias = null)
    {
        $this->min = \max(0, (int)$min);
        $this->max = \max(-1, (int)$max);

        \assert($this->min <= $this->max || $this->max === -1,
            \sprintf('Cannot repeat with a min (%d) greater than max (%d).', $this->min, $this->max));


        parent::__construct($name, (array)$then, $alias);
    }

    /**
     * Get minimum bound.
     *
     * @return int
     */
    public function getMin(): int
    {
        return $this->min;
    }

    /**
     * Get maximum bound.
     *
     * @return int
     */
    public function getMax(): int
    {
        return $this->max;
    }
}

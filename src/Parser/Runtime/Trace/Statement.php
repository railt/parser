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
 * Class Invocation
 */
abstract class Statement extends TraceItem implements StmtInterface
{
    /**
     * @var int
     */
    protected $state;

    /**
     * @var array
     */
    protected $jumps;

    /**
     * Invocation constructor.
     *
     * @param string|int $rule
     * @param int $state
     * @param array $jumps
     */
    public function __construct($rule, int $state = 0, array $jumps = [])
    {
        parent::__construct($rule);

        $this->state = $state;
        $this->jumps = $jumps;
    }

    /**
     * @return array
     */
    public function getJumps(): array
    {
        return $this->jumps;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }
}

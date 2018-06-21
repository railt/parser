<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime\Trace;

use Railt\Parser\Rule\Production;
use Railt\Parser\Rule\Symbol;

/**
 * Class Invocation
 */
abstract class Invocation implements TraceInterface
{
    /**
     * @var Symbol
     */
    protected $rule;

    /**
     * @var int
     */
    protected $next;

    /**
     * @var array
     */
    protected $todo;

    /**
     * @var int
     */
    private $offset = 0;

    /**
     * Invocation constructor.
     * @param Symbol $rule
     * @param int $next
     * @param array|null $then
     */
    public function __construct(Symbol $rule, int $next = 0, array $then = null)
    {
        $this->rule  = $rule;
        $this->next  = $next;
        $this->todo  = $then;
    }

    /**
     * @param int $offset
     * @return TraceInterface
     */
    public function at(int $offset): TraceInterface
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return Symbol
     */
    public function getRule(): Symbol
    {
        return $this->rule;
    }

    /**
     * @return int
     */
    public function getRuleId(): int
    {
        return $this->rule->getId();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        if ($this->rule instanceof Production && $this->rule->getName()) {
            return $this->rule->getName();
        }

        return (string)$this->getRuleId();
    }

    /**
     * @return int
     */
    public function getNext(): int
    {
        return $this->next;
    }

    /**
     * @return array
     */
    public function getTodo(): array
    {
        return $this->todo;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return bool
     */
    public function isKept(): bool
    {
        if ($this->rule instanceof Production) {
            return $this->rule->getName() !== null;
        }

        return false;
    }
}

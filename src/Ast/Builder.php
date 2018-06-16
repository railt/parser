<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Ast;

use Railt\Parser\Ast\Rule as AstRule;
use Railt\Parser\Runtime\Trace\Entry;
use Railt\Parser\Runtime\Trace\Escape;
use Railt\Parser\Runtime\Trace\TokenTrace;

/**
 * Class Builder
 */
class Builder
{
    /**
     * @var \Iterator|\Traversable
     */
    private $trace;

    /**
     * @var array
     */
    private $delegates;

    /**
     * Builder constructor.
     * @param iterable $trace
     * @param array $delegates
     */
    public function __construct(iterable $trace, array $delegates = [])
    {
        $this->trace = \is_array($trace) ? new \ArrayIterator($trace) : $trace;
        $this->delegates = $delegates;
    }

    /**
     * @return RuleInterface
     * @throws \LogicException
     */
    public function reduce(): RuleInterface
    {
        if (! $this->trace->current()) {
            throw new \LogicException('Could not create AST from empty trace');
        }

        return $this->build($this->trace)->current();
    }

    /**
     * @param \Iterator $iterator
     * @return \Traversable|\Iterator
     */
    private function build(\Iterator $iterator): \Traversable
    {
        while ($iterator->valid() && $current = $iterator->current()) {
            $iterator->next();

            switch (true) {
                case $current instanceof TokenTrace:
                    if ($current->isKept()) {
                        yield $this->leaf($current);
                    }

                    break;
                case $current instanceof Entry:
                    if ($current->isKept()) {
                        yield $this->rule($current, $this->build($iterator));
                    }

                    break;
                case $current instanceof Escape:
                    if ($current->isKept()) {
                        break 2;
                    }
            }
        }
    }

    /**
     * @param TokenTrace $terminal
     * @return LeafInterface
     */
    private function leaf(TokenTrace $terminal): LeafInterface
    {
        return new Leaf($terminal->getName(), $terminal->getValue(), $terminal->getOffset());
    }

    /**
     * @param Entry $entry
     * @param \Iterator $children
     * @return RuleInterface
     */
    private function rule(Entry $entry, \Iterator $children): RuleInterface
    {
        $name = \ltrim($entry->getName(), '#');
        $class = $this->delegates[$entry->getName()] ?? AstRule::class;

        return new $class($name, \iterator_to_array($children), $entry->getOffset());
    }
}

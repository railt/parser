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
use Railt\Parser\Environment;
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
     * @var Environment
     */
    private $env;

    /**
     * Builder constructor.
     * @param iterable $trace
     * @param array $delegates
     * @param Environment|null $env
     */
    public function __construct(iterable $trace, array $delegates = [], Environment $env = null)
    {
        $this->delegates = $delegates;
        $this->env = $env ?? new Environment();
        $this->trace = \is_array($trace) ? new \ArrayIterator($trace) : $trace;
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
     * @param iterable $children
     * @return RuleInterface
     */
    private function rule(Entry $entry, iterable $children): RuleInterface
    {
        $name = $this->getRuleName($entry);
        $class = $this->getRuleClass($entry);
        $children = $this->getRuleChildren($children);

        return new $class($this->env, $name, $children, $entry->getOffset());
    }

    /**
     * @param iterable $children
     * @return array
     */
    private function getRuleChildren(iterable $children): array
    {
        if ($children instanceof \Traversable) {
            return \iterator_to_array($children);
        }

        return $children;
    }

    /**
     * @param Entry $entry
     * @return string
     */
    private function getRuleName(Entry $entry): string
    {
        return \ltrim($entry->getName(), '#');
    }

    /**
     * @param Entry $entry
     * @return string|Delegate
     */
    private function getRuleClass(Entry $entry): string
    {
        return $this->delegates[$entry->getName()] ?? AstRule::class;
    }
}

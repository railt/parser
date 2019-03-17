<?php
/**
 * This file is part of compiler package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime;

use Railt\Parser\Ast\Leaf;
use Railt\Parser\Ast\Node;
use Railt\Parser\Ast\RuleInterface;
use Railt\Parser\Grammar;
use Railt\Parser\Runtime\Trace\Entry;
use Railt\Parser\Runtime\Trace\Escape;
use Railt\Parser\Runtime\Trace\LexemeInterface;
use Railt\Parser\Runtime\Trace\StmtInterface;
use Railt\Parser\Runtime\Trace\TraceInterface;

/**
 * Class Builder
 */
class Builder implements BuilderInterface
{
    /**
     * @var array|TraceInterface[]|LexemeInterface[]|StmtInterface[]
     */
    private $trace;

    /**
     * @var \Closure
     */
    private $reduce;

    /**
     * @var Grammar
     */
    private $grammar;

    /**
     * Builder constructor.
     *
     * @param array $trace
     * @param Grammar $grammar
     * @param \Closure $reduce
     */
    public function __construct(array $trace, Grammar $grammar, \Closure $reduce)
    {
        $this->trace = $trace;
        $this->reduce = $reduce;
        $this->grammar = $grammar;
    }

    /**
     * @return RuleInterface|mixed
     * @throws \LogicException
     */
    public function build()
    {
        return $this->buildTree();
    }

    /**
     * Build AST from trace.
     * Walk through the trace iteratively and recursively.
     *
     * @param int $i Current trace index.
     * @param array &$children Collected children.
     * @return Node|int|mixed
     * @throws \LogicException
     */
    protected function buildTree(int $i = 0, array &$children = [])
    {
        $max = \count($this->trace);

        while ($i < $max) {
            $trace = $this->trace[$i];
            $name = $trace->getName();

            if ($trace instanceof Entry) {
                $nextTrace = $this->trace[$i + 1];
                $id = $this->grammar->getNodeId($name);

                // Optimization: Skip empty trace sequence.
                if ($nextTrace instanceof Escape && $name === $nextTrace->getName()) {
                    $i += 2;

                    continue;
                }

                if (! $this->grammar->isTransitional($name)) {
                    $children[] = $name;
                }

                if ($id !== null) {
                    $children[] = [$id];
                }

                $i = $this->buildTree($i + 1, $children);

                if ($this->grammar->isTransitional($name)) {
                    continue;
                }

                $handle = [];
                $childId = null;

                do {
                    $pop = \array_pop($children);

                    if (\is_object($pop) === true) {
                        $handle[] = $pop;
                    } elseif (\is_array($pop) && $childId === null) {
                        $childId = \reset($pop);
                    } elseif ($name === $pop) {
                        break;
                    }
                } while ($pop !== null);

                if ($childId === null) {
                    $childId = $this->grammar->getDefaultId($name);
                }

                if ($childId === null) {
                    for ($j = \count($handle) - 1; $j >= 0; --$j) {
                        $children[] = $handle[$j];
                    }

                    continue;
                }

                $children[] = ($this->reduce)((string)($id ?: $childId), \array_reverse($handle), $trace->getOffset());
            } elseif ($trace instanceof Escape) {
                return $i + 1;
            } else {
                if (! $trace->isKept()) {
                    ++$i;
                    continue;
                }

                $children[] = new Leaf($trace->getName(), $trace->getValue(), $trace->getOffset());
                ++$i;
            }
        }

        return $children[0];
    }
}

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
use Railt\Parser\Trace\Entry;
use Railt\Parser\Trace\Escape;

/**
 * Class Builder
 */
class Builder implements BuilderInterface
{
    /**
     * @var array
     */
    private $trace;

    /**
     * @var array
     */
    private $rules;

    /**
     * @var \Closure
     */
    private $reduce;

    /**
     * Builder constructor.
     *
     * @param array $trace
     * @param array $rules
     * @param \Closure $reduce
     */
    public function __construct(array $trace, array $rules, \Closure $reduce)
    {
        $this->trace = $trace;
        $this->rules = $rules;
        $this->reduce = $reduce;
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
     * @return Node|int
     * @throws \LogicException
     */
    protected function buildTree(int $i = 0, array &$children = [])
    {
        $max = \count($this->trace);

        while ($i < $max) {
            $trace = $this->trace[$i];

            if ($trace instanceof Entry) {
                $ruleName = $trace->getRule();
                $rule = $this->rules[$ruleName];
                $isRule = $trace->isTransitional() === false;
                $nextTrace = $this->trace[$i + 1];
                $id = $rule->getNodeId();

                // Optimization: Skip empty trace sequence.
                if ($nextTrace instanceof Escape && $ruleName === $nextTrace->getRule()) {
                    $i += 2;

                    continue;
                }

                if ($isRule === true) {
                    $children[] = $ruleName;
                }

                if ($id !== null) {
                    $children[] = [$id];
                }

                $i = $this->buildTree($i + 1, $children);

                if ($isRule === false) {
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
                    } elseif ($ruleName === $pop) {
                        break;
                    }
                } while ($pop !== null);

                if ($childId === null) {
                    $childId = $rule->getDefaultId();
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

                $children[] = new Leaf($trace->getToken());
                ++$i;
            }
        }

        return $children[0];
    }
}

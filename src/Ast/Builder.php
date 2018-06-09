<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Ast;

use Railt\Parser\Rule\RulesContainerInterface;
use Railt\Parser\Runtime\Trace\Entry;
use Railt\Parser\Runtime\Trace\Escape;
use Railt\Parser\Runtime\Trace\Invocation;
use Railt\Parser\Runtime\Trace\Terminator;
use Railt\Parser\Iterator\Buffer;
use Railt\Parser\Iterator\BufferInterface;
use Railt\Lexer\Result\Unknown;
use Railt\Lexer\LexerInterface;
use Railt\Parser\Ast\Leaf;
use Railt\Parser\Ast\Node;
use Railt\Parser\Ast\NodeInterface;
use Railt\Parser\Ast\Rule as AstRule;

/**
 * Class TreeBuilder
 */
class Builder
{
    /**
     * @var iterable
     */
    private $trace;

    /**
     * @var RulesContainerInterface
     */
    private $rules;

    /**
     * @var int
     */
    private $size;

    /**
     * Builder constructor.
     * @param RulesContainerInterface $rules
     * @param iterable $trace
     */
    public function __construct(RulesContainerInterface $rules, iterable $trace)
    {
        $this->trace = $trace;
        $this->rules = $rules;
        $this->size = \count($this->trace);
    }

    /**
     * @return RuleInterface
     */
    public function reduce(): RuleInterface
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
     */
    protected function buildTree(int $i = 0, array &$children = [])
    {
        while ($i < $this->size) {
            /** @var Invocation|Terminator $trace */
            $trace = $this->trace[$i];

            if ($trace instanceof Entry) {
                $ruleName  = $trace->getRule();
                $rule      = $this->rules->fetch($ruleName);
                $isRule    = $trace->isTransitional() === false;
                $nextTrace = $this->trace[$i + 1];
                $id        = $rule->getName();
                $offset    = $trace->getOffset();

                // Optimization: Skip empty trace sequence.
                if ($nextTrace instanceof Escape && $ruleName === $nextTrace->getRule()) {
                    $i += 2;

                    continue;
                }

                if ($isRule === true) {
                    $children[] = $ruleName;
                }

                if ($id !== null) {
                    $children[] = [
                        'id' => $id,
                    ];
                }

                $i = $this->buildTree($i + 1, $children);

                if ($isRule === false) {
                    continue;
                }

                $handle = [];
                $cId    = null;

                do {
                    $pop = \array_pop($children);

                    if (\is_object($pop) === true) {
                        $handle[] = $pop;
                    } elseif (\is_array($pop) === true && $cId === null) {
                        $cId = $pop['id'];
                    } elseif ($ruleName === $pop) {
                        break;
                    }
                } while ($pop !== null);

                if ($cId === null) {
                    for ($j = \count($handle) - 1; $j >= 0; --$j) {
                        $children[] = $handle[$j];
                    }

                    continue;
                }

                $rule = new AstRule((string)($id ?: $cId), \array_reverse($handle), $offset);
                $children[] = $rule;
            } elseif ($trace instanceof Escape) {
                return $i + 1;
            } else {
                if ($trace->isKept() === false) {
                    ++$i;
                    continue;
                }

                $children[] = $this->leaf($trace);
                ++$i;
            }
        }

        return $children[0];
    }

    /**
     * @param Terminator $terminal
     * @return LeafInterface
     */
    private function leaf(Terminator $terminal): LeafInterface
    {
        return new Leaf($terminal->getName(), $terminal->getValue(), $terminal->getOffset());
    }
}

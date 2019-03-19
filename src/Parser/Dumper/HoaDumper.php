<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Dumper;

use Railt\Parser\Ast\LeafInterface;
use Railt\Parser\Ast\RuleInterface;

/**
 * Class HoaDumper
 */
class HoaDumper extends Dumper
{
    /**
     * @var string
     */
    private const LEAF_PATTERN = 'token(%s, %s)';

    /**
     * @var string
     */
    private const LEAF_UNKNOWN = 'token(%s)';

    /**
     * @param mixed|RuleInterface $rule
     * @param int $depth
     * @return string
     */
    protected function rule($rule, int $depth = 0): string
    {
        $isRule = \is_iterable($rule);

        if (! $isRule) {
            return $this->leaf($rule, $depth);
        }

        $content = [
            $this->prefix($depth) . ($rule instanceof RuleInterface ? $rule->getName() : \get_class($rule))
        ];

        if (\is_iterable($rule)) {
            foreach ($rule as $child) {
                $content[] = $this->rule($child, $depth + 1);
            }
        }

        return \implode("\n", $content);
    }

    /**
     * @param mixed|LeafInterface $leaf
     * @param int $depth
     * @return string
     */
    protected function leaf($leaf, int $depth = 0): string
    {
        if ($leaf instanceof LeafInterface) {
            return $this->prefix($depth) . \sprintf(self::LEAF_PATTERN, $leaf->getName(), $leaf->getValue());
        }

        return \sprintf(self::LEAF_UNKNOWN, \get_class($leaf));
    }

    /**
     * @param int $depth
     * @return string
     */
    private function prefix(int $depth): string
    {
        return \str_repeat('>  ', $depth + 1);
    }
}

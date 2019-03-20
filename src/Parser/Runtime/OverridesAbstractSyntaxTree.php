<?php
/**
 * This file is part of parser package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime;

use Railt\Parser\Ast\LeafInterface;
use Railt\Parser\Ast\RuleInterface;

/**
 * Interface OverridesAbstractSyntaxTree
 */
interface OverridesAbstractSyntaxTree
{
    /**
     * @param string $token
     * @param string $value
     * @param int $offset
     * @return LeafInterface|mixed
     */
    public function leaf(string $token, string $value, int $offset);

    /**
     * @param string $rule
     * @param array $children
     * @param int $offset
     * @return RuleInterface|mixed
     */
    public function rule(string $rule, array $children, int $offset);
}

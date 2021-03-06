<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Node\Value;

use Railt\Parser\Node\Node;
use Railt\Parser\Node\DefinitionNode;

/**
 * Class Value
 *
 * <code>
 *  export type ValueNode =
 *      | VariableNode
 *      | IntValueNode
 *      | FloatValueNode
 *      | StringValueNode
 *      | BooleanValueNode
 *      | NullValueNode
 *      | EnumValueNode
 *      | ListValueNode
 *      | ObjectValueNode
 *      ;
 * </code>
 *
 * @see https://github.com/graphql/graphql-js/blob/v14.5.7/src/language/ast.js#L301
 */
abstract class ValueNode extends DefinitionNode
{
}

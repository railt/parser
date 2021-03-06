<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Node\Value;

use Railt\Parser\Node\Generic\ValueCollection;

/**
 * Class ListValueNode
 *
 * <code>
 *  export type ListValueNode = {
 *      +kind: 'ListValue',
 *      +loc?: Location,
 *      +values: $ReadOnlyArray<ValueNode>,
 *      ...
 *  };
 * </code>
 *
 * @see https://github.com/graphql/graphql-js/blob/v14.5.7/src/language/ast.js#L354
 */
class ListValueNode extends ValueNode
{
    /**
     * @var ValueCollection
     */
    public ValueCollection $values;

    /**
     * ListValue constructor.
     *
     * @param ValueCollection $values
     */
    public function __construct(ValueCollection $values)
    {
        $this->values = $values;
    }
}

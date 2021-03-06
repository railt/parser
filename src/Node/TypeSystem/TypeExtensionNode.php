<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Node\TypeSystem;

use Railt\Parser\Node\NameNode;
use Railt\Parser\Node\Value\StringValueNode;
use Railt\Parser\Node\Generic\DirectiveCollection;

/**
 * Class TypeExtensionNode
 *
 * <code>
 *  export type TypeExtensionNode =
 *      | ScalarTypeExtensionNode
 *      | ObjectTypeExtensionNode
 *      | InterfaceTypeExtensionNode
 *      | UnionTypeExtensionNode
 *      | EnumTypeExtensionNode
 *      | InputObjectTypeExtensionNode
 *      ;
 * </code>
 *
 * @see https://github.com/graphql/graphql-js/blob/v14.5.7/src/language/ast.js#L562
 */
abstract class TypeExtensionNode extends TypeSystemExtensionNode
{
    /**
     * @var NameNode
     */
    public NameNode $name;

    /**
     * @var StringValueNode|null
     */
    public ?StringValueNode $description = null;

    /**
     * @var DirectiveCollection|null
     */
    public ?DirectiveCollection $directives = null;

    /**
     * TypeExtensionNode constructor.
     *
     * @param NameNode $name
     */
    public function __construct(NameNode $name)
    {
        $this->name = $name;
    }
}

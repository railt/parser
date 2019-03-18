<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Builder\Definition;

use Railt\Parser\Builder\DefinitionInterface;

/**
 * Class Definition
 */
abstract class Definition implements DefinitionInterface
{
    /**
     * @var string|int
     */
    protected $id;

    /**
     * Definition constructor.
     *
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }
}

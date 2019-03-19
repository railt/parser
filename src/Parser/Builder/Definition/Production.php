<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Builder\Definition;

use Railt\Parser\Builder\ProductionDefinitionInterface;

/**
 * Class Production
 */
abstract class Production extends Definition implements ProductionDefinitionInterface
{
    /**
     * @var string|null
     */
    protected $alias;

    /**
     * @var array|int[]|string[]
     */
    protected $goto;

    /**
     * Production constructor.
     *
     * @param string|int $name
     * @param array|string[]|int[] $goto
     * @param string|null $alias
     */
    public function __construct($name, array $goto, string $alias = null)
    {
        $this->alias = $alias;
        $this->goto = $goto;

        parent::__construct($name);
    }

    /**
     * @return array|string[]|int[]
     */
    public function getGoto(): array
    {
        return $this->goto;
    }

    /**
     * @param string $alias
     * @return ProductionDefinitionInterface|$this
     */
    public function as(string $alias): ProductionDefinitionInterface
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }
}

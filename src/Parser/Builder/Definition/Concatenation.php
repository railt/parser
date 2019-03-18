<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Builder\Definition;

/**
 * Class Concatenation
 */
class Concatenation extends Production
{
    /**
     * @var array
     */
    private $goto;

    /**
     * Alternation constructor.
     *
     * @param string|int $name
     * @param array $goto
     * @param string|null $alias
     */
    public function __construct($name, array $goto, ?string $alias = null)
    {
        $this->goto = $goto;

        parent::__construct($name, $alias);
    }

    /**
     * @return array
     */
    public function getGoto(): array
    {
        return $this->goto;
    }
}

<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Rule;

/**
 * Class Token
 */
class Token extends BaseSymbol implements Terminal
{
    /**
     * @var string
     */
    protected $name;

    /**
     * Token constructor.
     * @param string|int $id
     * @param string $name
     * @param bool $keep
     */
    public function __construct($id, string $name, bool $keep = false)
    {
        $this->name = $name;
        parent::__construct($id, $keep);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}

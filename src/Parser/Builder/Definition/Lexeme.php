<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Builder\Definition;

use Railt\Parser\Builder\LexemeDefinitionInterface;

/**
 * Class Lexeme
 */
class Lexeme extends Definition implements LexemeDefinitionInterface
{
    /**
     * Token name.
     *
     * @var string
     */
    protected $name;

    /**
     * Whether the token is kept or not in the AST.
     *
     * @var bool
     */
    protected $kept = false;

    /**
     * Token constructor.
     *
     * @param string|int $name Name.
     * @param string $tokenName Token name.
     * @param bool $kept Whether the token is kept or not in the AST.
     */
    public function __construct($name, string $tokenName, bool $kept = false)
    {
        parent::__construct($name);

        $this->name = $tokenName;
        $this->kept = $kept;
    }

    /**
     * @param bool $keep
     * @return LexemeDefinitionInterface|$this
     */
    public function keep(bool $keep = true): LexemeDefinitionInterface
    {
        $this->kept = $keep;

        return $this;
    }

    /**
     * Get token name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check whether the token is kept in the AST or not.
     *
     * @return bool
     */
    public function isKept(): bool
    {
        return $this->kept;
    }
}

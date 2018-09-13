<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Ast;

use Railt\Lexer\TokenInterface;
use Railt\Parser\Environment;

/**
 * Class Leaf
 */
class Leaf extends Node implements LeafInterface
{
    /**
     * @var array
     */
    private $value;

    /**
     * Leaf constructor.
     * @param Environment $env
     * @param TokenInterface $token
     */
    public function __construct(Environment $env, TokenInterface $token)
    {
        parent::__construct($env, $token->getName(), $token->getOffset());

        $this->value = $token->getGroups();
    }

    /**
     * @param int $group
     * @return null|string
     */
    public function getValue(int $group = 0): ?string
    {
        return $this->value[$group] ?? null;
    }

    /**
     * @return iterable|string[]
     */
    public function getValues(): iterable
    {
        return $this->value;
    }
}

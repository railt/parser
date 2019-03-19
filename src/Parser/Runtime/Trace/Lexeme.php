<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime\Trace;

use Railt\Lexer\TokenInterface;

/**
 * Class Token
 */
class Lexeme extends TraceItem implements LexemeInterface
{
    /**
     * @var bool
     */
    private $kept;

    /**
     * @var string
     */
    private $value;

    /**
     * Token constructor.
     *
     * @param TokenInterface $token
     * @param bool $kept
     */
    public function __construct(TokenInterface $token, bool $kept = false)
    {
        parent::__construct($token->getName());
        $this->at($token->getOffset());
        $this->value = $token->getValue();

        $this->kept = $kept;
    }

    /**
     * @return bool
     */
    public function isKept(): bool
    {
        return $this->kept;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}

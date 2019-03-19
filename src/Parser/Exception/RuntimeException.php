<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Exception;

use Railt\Lexer\TokenInterface;

/**
 * Class RuntimeException
 */
class RuntimeException extends \RuntimeException
{
    /**
     * @var string
     */
    private const MESSAGE = 'Unexpected token %s';

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * RuntimeException constructor.
     *
     * @param TokenInterface $token
     * @param int $code
     */
    public function __construct(TokenInterface $token, int $code = 0)
    {
        $this->token = $token;

        parent::__construct(\sprintf(self::MESSAGE, $token), $code);
    }

    /**
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }
}

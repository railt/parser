<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime\Trace;

/**
 * Class Token
 */
class Lexeme extends TraceItem implements LexemeInterface
{
    /**
     * @var string
     */
    private $value;

    /**
     * Lexeme constructor.
     *
     * @param int $id
     * @param string $value
     */
    public function __construct(int $id, string $value)
    {
        parent::__construct($id);

        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}

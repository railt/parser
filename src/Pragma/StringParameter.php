<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Pragma;

/**
 * Class StringParameter
 */
class StringParameter extends Parameter
{
    /**
     * @param string $value
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function parse($value): string
    {
        if (\is_scalar($value)) {
            return (string)$value;
        }

        throw $this->invalidParameter('string');
    }
}

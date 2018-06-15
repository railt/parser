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
 * Class BooleanParameter
 */
class BooleanParameter extends Parameter
{
    /**
     * @param mixed $value
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function parse($value): bool
    {
        switch (true) {
            case \is_string($value):
                return $this->parseString($value);
            case \is_scalar($value):
                return (bool)$value;
        }

        throw $this->invalidParameter('bool');
    }

    /**
     * @param $value
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function parseString($value): bool
    {
        switch (\strtolower(\trim($value))) {
            case 'true':
            case '1':
                return true;
            case 'false':
            case '0':
                return false;
            default:
                throw $this->invalidParameter('bool', 'int', 'string');
        }
    }
}

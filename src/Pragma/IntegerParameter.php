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
 * Class IntegerParameter
 */
class IntegerParameter extends Parameter
{
    /**
     * @param $value
     * @return int
     * @throws \InvalidArgumentException
     */
    protected function parse($value): int
    {
        switch (true) {
            case \is_int($value):
                return $value;
            case \is_string($value):
                return $this->parseString($value);
        }

        throw $this->invalidParameter('string', 'int', 'float');
    }

    /**
     * @param string $value
     * @return int
     * @throws \InvalidArgumentException
     */
    private function parseString(string $value): int
    {
        $after = (string)(int)$value;

        if ($after !== $value) {
            throw $this->invalidParameter('int');
        }

        return (int)$value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return parent::getValue();
    }
}

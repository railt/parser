<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser;

use Railt\Parser\Pragma\ParameterInterface;

/**
 * Class Configuration
 */
class Configuration
{
    /**
     * @var array
     */
    private $params = [];

    /**
     * Configuration constructor.
     * @param iterable $parameters
     */
    public function __construct(iterable $parameters = [])
    {
        foreach ($parameters as $parameter) {
            $this->add($parameter);
        }
    }

    /**
     * @param ParameterInterface $parameter
     * @return Configuration
     */
    public function add(ParameterInterface $parameter): Configuration
    {
        $this->params[$parameter->getName()] = $parameter->getValue();

        return $this;
    }

    /**
     * @param string $parameter
     * @param null $default
     * @return mixed|null
     */
    public function get(string $parameter, $default = null)
    {
        return $this->params[$parameter] ?? $default;
    }
}

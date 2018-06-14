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
 * Class Parameter
 */
abstract class Parameter implements ParameterInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * Value constructor.
     * @param string $name
     * @param $value
     */
    public function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $this->parse($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    abstract protected function parse($value);

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string ...$shouldBe
     * @return \InvalidArgumentException
     */
    protected function invalidParameter(string ...$shouldBe): \InvalidArgumentException
    {
        $needle = \count($shouldBe) === 1
            ? 'a compatible ' . \reset($shouldBe)
            : 'one of compatible ' . \implode(',', $shouldBe);

        $error = \vsprintf('Parameter %s should be %s, but %s given', [
            $this->getName(),
            $needle,
            \is_object($this->value) ? 'instance of ' . \get_class($this->value) : \gettype($this->value)
        ]);

        return new \InvalidArgumentException($error);
    }
}

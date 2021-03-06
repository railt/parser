<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Node;

use Phplrt\Contracts\Ast\NodeInterface;
use Railt\Parser\Node\Common\ResolvableTrait;
use Railt\Parser\Node\Common\ReadOnlyAttributesTrait;

/**
 * Class Node
 *
 * @property-read string $kind
 */
abstract class Node implements NodeInterface, \JsonSerializable
{
    use ResolvableTrait;
    use ReadOnlyAttributesTrait;

    /**
     * @var Location|null
     */
    public ?Location $loc = null;

    /**
     * @return \Traversable|NodeInterface[]
     */
    public function getIterator(): \Traversable
    {
        foreach (\get_object_vars($this) as $property => $value) {
            if ($value instanceof NodeInterface) {
                yield $property => $value;
            }
        }
    }

    /**
     * @param Location|null $location
     * @return Node|$this
     */
    public function locatedIn(?Location $location): self
    {
        $this->loc = $location;

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->loc ? $this->loc->start : 0;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        try {
            return (string)\json_encode($this->jsonSerialize(), \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return (string)\var_export($this, true);
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $properties = \array_merge(['kind' => $this->getKind()], \get_object_vars($this));

        if (isset($properties['attributes']) && \is_array($properties['attributes'])) {
            $properties = \array_merge($properties, $properties['attributes']);

            unset($properties['attributes']);
        }

        if (isset($properties['loc'])) {
            unset($properties['loc']);
        }

        return $properties;
    }

    /**
     * @return string
     */
    public function getKind(): string
    {
        $fqn = \str_replace('\\', \DIRECTORY_SEPARATOR, static::class);

        return \basename($fqn, 'Node');
    }
}

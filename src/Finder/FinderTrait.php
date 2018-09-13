<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Finder;

use Railt\Parser\Ast\NodeInterface;
use Railt\Parser\Finder;

/**
 * Trait FinderTrait
 */
trait FinderTrait
{
    /**
     * @param string $name
     * @param int|null $depth
     * @return iterable
     * @throws \InvalidArgumentException
     * @throws \Railt\Lexer\Exception\BadLexemeException
     */
    public function find(string $name, int $depth = null): iterable
    {
        return Finder::new($this->getFinderNode())->depth($depth)->query($name);
    }

    /**
     * @return NodeInterface
     */
    abstract protected function getFinderNode(): NodeInterface;

    /**
     * @param string $name
     * @param int|null $depth
     * @return null|NodeInterface
     * @throws \InvalidArgumentException
     * @throws \Railt\Lexer\Exception\BadLexemeException
     * @throws \Railt\Parser\Exception\UnexpectedTokenException
     * @throws \Railt\Parser\Exception\UnrecognizedTokenException
     */
    public function first(string $name, int $depth = null): ?NodeInterface
    {
        return Finder::new($this->getFinderNode())->depth($depth)->first($name);
    }
}

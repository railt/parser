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
     * @param string $query
     * @param int|null $depth
     * @return Finder
     * @throws \InvalidArgumentException
     * @throws \Railt\Lexer\Exception\BadLexemeException
     */
    public function find(string $query, int $depth = null): Finder
    {
        return Finder::new($this->getFinderNode())->depth($depth)->where($query);
    }

    /**
     * @return NodeInterface
     */
    abstract protected function getFinderNode(): NodeInterface;

    /**
     * @param string $query
     * @param int|null $depth
     * @return null|NodeInterface
     * @throws \InvalidArgumentException
     * @throws \Railt\Lexer\Exception\BadLexemeException
     * @throws \Railt\Parser\Exception\UnexpectedTokenException
     * @throws \Railt\Parser\Exception\UnrecognizedTokenException
     */
    public function first(string $query, int $depth = null): ?NodeInterface
    {
        return Finder::new($this->getFinderNode())->depth($depth)->where($query)->first();
    }
}

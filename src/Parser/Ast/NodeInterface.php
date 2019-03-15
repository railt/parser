<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Ast;

use Railt\Parser\Dumper\NodeDumperInterface;

/**
 * Interface NodeInterface
 */
interface NodeInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     * @return bool
     */
    public function is(string $name): bool;

    /**
     * @return int
     */
    public function getOffset(): int;

    /**
     * @return string
     */
    public function getValue(): string;

    /**
     * @param NodeDumperInterface|string $dumper
     * @return string
     */
    public function dump(string $dumper): string;
}

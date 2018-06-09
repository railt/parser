<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser\Runtime;

use Railt\Io\Readable;
use Railt\Parser\Iterator\BufferInterface;
use Railt\Parser\Rule\RulesContainerInterface;
use Railt\Parser\Rule\Symbol;

/**
 * Interface RuntimeInterface
 */
interface RuntimeInterface
{
    /**
     * RuntimeInterface constructor.
     * @param RulesContainerInterface $rules
     * @param Symbol $root
     */
    public function __construct(RulesContainerInterface $rules, Symbol $root);

    /**
     * @param Readable $input
     * @param BufferInterface $buffer
     * @return iterable
     */
    public function parse(Readable $input, BufferInterface $buffer): iterable;
}

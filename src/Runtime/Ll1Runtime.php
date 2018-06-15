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
use Railt\Parser\Iterator\Buffer;
use Railt\Parser\Iterator\BufferInterface;

/**
 * Class Ll1Runtime
 */
class Ll1Runtime extends LlkRuntime
{
    /**
     * @param Readable $input
     * @param BufferInterface $buffer
     * @return iterable
     * @throws \Railt\Io\Exception\ExternalFileException
     */
    public function parse(Readable $input, BufferInterface $buffer): iterable
    {
        return parent::parse($input, new Buffer($buffer, 1));
    }
}

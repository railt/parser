<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Parser;

use Railt\Io\Readable;
use Railt\Parser\Runtime\StreamInterface;

/**
 * Interface ParserRuntimeInterface
 */
interface RuntimeInterface
{
    /**
     * @param StreamInterface $tokens
     * @return mixed
     */
    public function parse(StreamInterface $tokens);
}

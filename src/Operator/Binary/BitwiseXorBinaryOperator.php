<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Operator\Binary;

use Twig\Node\Expression\Binary\BitwiseXorBinary;

class BitwiseXorBinaryOperator extends AbstractBinaryOperator
{
    public function getOperator(): string
    {
        return 'b-xor';
    }

    public function getPrecedence(): int
    {
        return 17;
    }

    public function getNodeClass(): ?string
    {
        return BitwiseXorBinary::class;
    }
}

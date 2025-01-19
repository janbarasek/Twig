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

use Twig\Node\Expression\Binary\BitwiseAndBinary;

class BitwiseAndBinaryOperator extends AbstractBinaryOperator
{
    public function getOperator(): string
    {
        return 'b-and';
    }

    public function getPrecedence(): int
    {
        return 18;
    }

    protected function getNodeClass(): string
    {
        return BitwiseAndBinary::class;
    }
}

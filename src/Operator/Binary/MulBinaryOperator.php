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

use Twig\Node\Expression\Binary\MulBinary;

class MulBinaryOperator extends AbstractBinaryOperator
{
    public function getOperator(): string
    {
        return '*';
    }

    public function getPrecedence(): int
    {
        return 60;
    }

    public function getNodeClass(): ?string
    {
        return MulBinary::class;
    }
}

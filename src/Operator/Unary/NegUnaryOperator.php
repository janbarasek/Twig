<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Operator\Unary;

use Twig\Node\Expression\Unary\NegUnary;

class NegUnaryOperator extends AbstractUnaryOperator
{
    public function getOperator(): string
    {
        return '-';
    }

    public function getPrecedence(): int
    {
        return 500;
    }

    public function getNodeClass(): ?string
    {
        return NegUnary::class;
    }
}

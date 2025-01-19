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

use Twig\Node\Expression\Unary\PosUnary;

class PosUnaryOperator extends AbstractUnaryOperator
{
    public function getOperator(): string
    {
        return '+';
    }

    public function getPrecedence(): int
    {
        return 500;
    }

    protected function getNodeClass(): string
    {
        return PosUnary::class;
    }
}

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

use Twig\Node\Expression\Unary\NotUnary;
use Twig\OperatorPrecedenceChange;

class NotUnaryOperator extends AbstractUnaryOperator
{
    public function getOperator(): string
    {
        return 'not';
    }

    public function getPrecedence(): int
    {
        return 50;
    }

    public function getPrecedenceChange(): ?OperatorPrecedenceChange
    {
        return new OperatorPrecedenceChange('twig/twig', '3.15', 70);
    }

    public function getNodeClass(): ?string
    {
        return NotUnary::class;
    }
}

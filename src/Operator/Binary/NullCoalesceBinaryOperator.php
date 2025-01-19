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

use Twig\Node\Expression\Binary\NullCoalesceBinary;
use Twig\Operator\OperatorAssociativity;
use Twig\OperatorPrecedenceChange;

class NullCoalesceBinaryOperator extends AbstractBinaryOperator
{
    public function getOperator(): string
    {
        return '??';
    }

    public function getPrecedence(): int
    {
        return 300;
    }

    public function getPrecedenceChange(): ?OperatorPrecedenceChange
    {
        return new OperatorPrecedenceChange('twig/twig', '3.15', 5);
    }

    public function getNodeClass(): ?string
    {
        return NullCoalesceBinary::class;
    }

    public function getAssociativity(): OperatorAssociativity
    {
        return OperatorAssociativity::Right;
    }
}

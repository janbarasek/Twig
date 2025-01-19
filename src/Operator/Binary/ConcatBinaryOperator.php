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

use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\OperatorPrecedenceChange;

class ConcatBinaryOperator extends AbstractBinaryOperator
{
    public function getOperator(): string
    {
        return '~';
    }

    public function getPrecedence(): int
    {
        return 40;
    }

    public function getPrecedenceChange(): ?OperatorPrecedenceChange
    {
        return new OperatorPrecedenceChange('twig/twig', '3.15', 27);
    }

    public function getNodeClass(): ?string
    {
        return ConcatBinary::class;
    }
}

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

use Twig\Node\Expression\Binary\AndBinary;

class AndBinaryOperator extends AbstractBinaryOperator
{
    public function getOperator(): string
    {
        return 'and';
    }

    public function getPrecedence(): int
    {
        return 15;
    }

    public function getNodeClass(): ?string
    {
        return AndBinary::class;
    }
}

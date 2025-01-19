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

class IsBinaryOperator extends AbstractBinaryOperator
{
    public function getPrecedence(): int
    {
        return 100;
    }

    public function getNodeClass(): ?string
    {
        return null;
    }

    public function getOperator(): string
    {
        return 'is';
    }
}

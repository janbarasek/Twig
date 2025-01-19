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

use Twig\Node\Expression\Binary\EqualBinary;

class EqualBinaryOperator extends AbstractBinaryOperator
{
    public function getOperator(): string
    {
        return '==';
    }

    public function getPrecedence(): int
    {
        return 20;
    }

    protected function getNodeClass(): string
    {
        return EqualBinary::class;
    }
}

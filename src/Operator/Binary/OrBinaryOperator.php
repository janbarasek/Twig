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

use Twig\Node\Expression\Binary\OrBinary;

class OrBinaryOperator extends AbstractBinaryOperator
{
    public function getOperator(): string
    {
        return 'or';
    }

    protected function getNodeClass(): string
    {
        return OrBinary::class;
    }

    public function getPrecedence(): int
    {
        return 10;
    }
}

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

use Twig\Node\Expression\Binary\ElvisBinary;
use Twig\Operator\OperatorAssociativity;

class ElvisBinaryOperator extends AbstractBinaryOperator
{
    public function getOperator(): string
    {
        return '?:';
    }

    public function getAliases(): array
    {
        return ['? :'];
    }

    public function getNodeClass(): ?string
    {
        return ElvisBinary::class;
    }

    public function getPrecedence(): int
    {
        return 5;
    }

    public function getAssociativity(): OperatorAssociativity
    {
        return OperatorAssociativity::Right;
    }
}

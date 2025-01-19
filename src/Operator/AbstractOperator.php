<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Operator;

use Twig\OperatorPrecedenceChange;

abstract class AbstractOperator implements OperatorInterface
{
    public function __toString(): string
    {
        return \sprintf('%s(%s)', $this->getArity()->value, $this->getOperator());
    }

    public function getPrecedenceChange(): ?OperatorPrecedenceChange
    {
        return null;
    }

    public function getAliases(): array
    {
        return [];
    }
}

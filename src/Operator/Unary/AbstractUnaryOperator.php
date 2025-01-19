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

use Twig\Operator\AbstractOperator;
use Twig\Operator\OperatorArity;

abstract class AbstractUnaryOperator extends AbstractOperator
{
    public function getArity(): OperatorArity
    {
        return OperatorArity::Unary;
    }
}

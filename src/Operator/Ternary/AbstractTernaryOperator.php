<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Operator\Ternary;

use Twig\Operator\AbstractOperator;
use Twig\Operator\OperatorArity;
use Twig\Operator\OperatorAssociativity;

abstract class AbstractTernaryOperator extends AbstractOperator implements TernaryOperatorInterface
{
    public function getArity(): OperatorArity
    {
        return OperatorArity::Ternary;
    }

    public function getAssociativity(): OperatorAssociativity
    {
        return OperatorAssociativity::Left;
    }
}

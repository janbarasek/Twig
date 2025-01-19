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

use Twig\ExpressionParser;
use Twig\Node\Expression\AbstractExpression;
use Twig\Operator\OperatorAssociativity;
use Twig\Operator\OperatorInterface;
use Twig\Token;

interface BinaryOperatorInterface extends OperatorInterface
{
    public function parse(ExpressionParser $parser, AbstractExpression $left, Token $token): AbstractExpression;

    public function getAssociativity(): OperatorAssociativity;
}

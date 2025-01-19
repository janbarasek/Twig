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
use Twig\Node\Expression\Unary\NotUnary;
use Twig\Token;

class IsNotBinaryOperator extends IsBinaryOperator
{
    public function parse(ExpressionParser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        return new NotUnary(parent::parse($parser, $expr, $token), $token->getLine());
    }

    public function getOperator(): string
    {
        return 'is not';
    }
}

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
use Twig\Node\Expression\ArrowFunctionExpression;
use Twig\Operator\AbstractOperator;
use Twig\Operator\OperatorArity;
use Twig\Operator\OperatorAssociativity;
use Twig\Token;

class ArrowBinaryOperator extends AbstractOperator implements BinaryOperatorInterface
{
    public function parse(ExpressionParser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        // As the expression of the arrow function is independent from the current precedence, we want a precedence of 0
        return new ArrowFunctionExpression($parser->parseExpression(), $expr, $token->getLine());
    }

    public function getOperator(): string
    {
        return '=>';
    }

    public function getPrecedence(): int
    {
        return 250;
    }

    public function getArity(): OperatorArity
    {
        return OperatorArity::Binary;
    }

    public function getAssociativity(): OperatorAssociativity
    {
        return OperatorAssociativity::Left;
    }
}

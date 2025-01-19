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

use Twig\ExpressionParser;
use Twig\Node\Expression\AbstractExpression;
use Twig\Operator\AbstractOperator;
use Twig\Operator\OperatorArity;
use Twig\Token;

abstract class AbstractUnaryOperator extends AbstractOperator implements UnaryOperatorInterface
{
    public function parse(ExpressionParser $parser, Token $token): AbstractExpression
    {
        return new ($this->getNodeClass())($parser->parseExpression($this->getPrecedence()), $token->getLine());
    }

    public function getArity(): OperatorArity
    {
        return OperatorArity::Unary;
    }

    /**
     * @return class-string<AbstractExpression>
     */
    abstract protected function getNodeClass(): string;
}

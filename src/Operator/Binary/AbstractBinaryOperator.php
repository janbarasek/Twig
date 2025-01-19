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
use Twig\Operator\AbstractOperator;
use Twig\Operator\OperatorArity;
use Twig\Operator\OperatorAssociativity;
use Twig\Token;

abstract class AbstractBinaryOperator extends AbstractOperator implements BinaryOperatorInterface
{
    public function parse(ExpressionParser $parser, AbstractExpression $left, Token $token): AbstractExpression
    {
        $right = $parser->parseExpression(OperatorAssociativity::Left === $this->getAssociativity() ? $this->getPrecedence() + 1 : $this->getPrecedence());

        return new ($this->getNodeClass())($left, $right, $token->getLine());
    }

    public function getArity(): OperatorArity
    {
        return OperatorArity::Binary;
    }

    public function getAssociativity(): OperatorAssociativity
    {
        return OperatorAssociativity::Left;
    }

    /**
     * @return class-string<AbstractExpression>
     */
    abstract protected function getNodeClass(): string;
}

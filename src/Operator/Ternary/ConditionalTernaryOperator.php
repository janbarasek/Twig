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

use Twig\ExpressionParser;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Ternary\ConditionalTernary;
use Twig\Token;

class ConditionalTernaryOperator extends AbstractTernaryOperator
{
    public function parse(ExpressionParser $parser, AbstractExpression $left, Token $token): AbstractExpression
    {
        $then = $parser->parseExpression($this->getPrecedence());
        if ($parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, $this->getElseOperator())) {
            // Ternary operator (expr ? expr2 : expr3)
            $else = $parser->parseExpression($this->getPrecedence());
        } else {
            // Ternary without else (expr ? expr2)
            $else = new ConstantExpression('', $token->getLine());
        }

        return new ConditionalTernary($left, $then, $else, $token->getLine());
    }

    public function getOperator(): string
    {
        return '?';
    }

    public function getPrecedence(): int
    {
        return 0;
    }

    private function getElseOperator(): string
    {
        return ':';
    }
}

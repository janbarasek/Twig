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
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Nodes;
use Twig\Operator\AbstractOperator;
use Twig\Operator\OperatorArity;
use Twig\Operator\OperatorAssociativity;
use Twig\Template;
use Twig\Token;

class SquareBracketBinaryOperator extends AbstractOperator implements BinaryOperatorInterface
{
    public function parse(ExpressionParser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        $stream = $parser->getStream();
        $lineno = $token->getLine();
        $arguments = new ArrayExpression([], $lineno);

        // slice?
        $slice = false;
        if ($stream->test(Token::PUNCTUATION_TYPE, ':')) {
            $slice = true;
            $attribute = new ConstantExpression(0, $token->getLine());
        } else {
            $attribute = $parser->parseExpression();
        }

        if ($stream->nextIf(Token::PUNCTUATION_TYPE, ':')) {
            $slice = true;
        }

        if ($slice) {
            if ($stream->test(Token::PUNCTUATION_TYPE, ']')) {
                $length = new ConstantExpression(null, $token->getLine());
            } else {
                $length = $parser->parseExpression();
            }

            $filter = $parser->getFilter('slice', $token->getLine());
            $arguments = new Nodes([$attribute, $length]);
            $filter = new ($filter->getNodeClass())($expr, $filter, $arguments, $token->getLine());

            $stream->expect(Token::PUNCTUATION_TYPE, ']');

            return $filter;
        }

        $stream->expect(Token::PUNCTUATION_TYPE, ']');

        return new GetAttrExpression($expr, $attribute, $arguments, Template::ARRAY_CALL, $lineno);
    }

    public function getOperator(): string
    {
        return '[';
    }

    public function getPrecedence(): int
    {
        return 300;
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

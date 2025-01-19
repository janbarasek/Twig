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

use Twig\Attribute\FirstClassTwigCallableReady;
use Twig\ExpressionParser;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Operator\AbstractOperator;
use Twig\Operator\OperatorArity;
use Twig\Operator\OperatorAssociativity;
use Twig\Token;

class FilterBinaryOperator extends AbstractOperator implements BinaryOperatorInterface
{
    private $readyNodes = [];

    public function parse(ExpressionParser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        $stream = $parser->getStream();
        $token = $stream->expect(Token::NAME_TYPE);
        $line = $token->getLine();

        if (!$stream->test(Token::OPERATOR_TYPE, '(')) {
            $arguments = new EmptyNode();
        } else {
            $arguments = $parser->parseNamedArguments();
        }

        $filter = $parser->getFilter($token->getValue(), $line);

        $ready = true;
        if (!isset($this->readyNodes[$class = $filter->getNodeClass()])) {
            $this->readyNodes[$class] = (bool) (new \ReflectionClass($class))->getConstructor()->getAttributes(FirstClassTwigCallableReady::class);
        }

        if (!$ready = $this->readyNodes[$class]) {
            trigger_deprecation('twig/twig', '3.12', 'Twig node "%s" is not marked as ready for passing a "TwigFilter" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.', $class);
        }

        return new $class($expr, $ready ? $filter : new ConstantExpression($filter->getName(), $line), $arguments, $line);
    }

    public function getOperator(): string
    {
        return '|';
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

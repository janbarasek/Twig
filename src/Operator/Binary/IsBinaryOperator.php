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
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Nodes;
use Twig\Operator\AbstractOperator;
use Twig\Operator\OperatorArity;
use Twig\Operator\OperatorAssociativity;
use Twig\Token;
use Twig\TwigTest;

class IsBinaryOperator extends AbstractOperator implements BinaryOperatorInterface
{
    private $readyNodes = [];

    public function parse(ExpressionParser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        $stream = $parser->getStream();
        $test = $parser->getTest($token->getLine());

        $arguments = null;
        if ($stream->test(Token::OPERATOR_TYPE, '(')) {
            $arguments = $parser->parseNamedArguments();
        } elseif ($test->hasOneMandatoryArgument()) {
            $arguments = new Nodes([0 => $parser->parseExpression($this->getPrecedence())]);
        }

        if ('defined' === $test->getName() && $expr instanceof NameExpression && null !== $alias = $parser->getImportedSymbol('function', $expr->getAttribute('name'))) {
            $expr = new MacroReferenceExpression($alias['node']->getNode('var'), $alias['name'], new ArrayExpression([], $expr->getTemplateLine()), $expr->getTemplateLine());
        }

        $ready = $test instanceof TwigTest;
        if (!isset($this->readyNodes[$class = $test->getNodeClass()])) {
            $this->readyNodes[$class] = (bool) (new \ReflectionClass($class))->getConstructor()->getAttributes(FirstClassTwigCallableReady::class);
        }

        if (!$ready = $this->readyNodes[$class]) {
            trigger_deprecation('twig/twig', '3.12', 'Twig node "%s" is not marked as ready for passing a "TwigTest" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.', $class);
        }

        return new $class($expr, $ready ? $test : $test->getName(), $arguments, $stream->getCurrent()->getLine());
    }

    public function getPrecedence(): int
    {
        return 100;
    }

    public function getOperator(): string
    {
        return 'is';
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

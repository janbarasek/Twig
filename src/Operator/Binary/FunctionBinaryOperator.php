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
use Twig\Error\SyntaxError;
use Twig\ExpressionParser;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\MacroReferenceExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Operator\AbstractOperator;
use Twig\Operator\OperatorArity;
use Twig\Operator\OperatorAssociativity;
use Twig\Token;

class FunctionBinaryOperator extends AbstractOperator implements BinaryOperatorInterface
{
    private $readyNodes = [];

    public function parse(ExpressionParser $parser, AbstractExpression $expr, Token $token): AbstractExpression
    {
        $line = $token->getLine();
        if (!$expr instanceof NameExpression) {
            throw new SyntaxError('Function name must be an identifier.', $line, $parser->getStream()->getSourceContext());
        }

        $name = $expr->getAttribute('name');

        if (null !== $alias = $parser->getImportedSymbol('function', $name)) {
            return new MacroReferenceExpression($alias['node']->getNode('var'), $alias['name'], $parser->parseCallableArguments($line, false), $line);
        }

        $args = $parser->parseNamedArguments(false);

        $function = $parser->getFunction($name, $line);

        if ($function->getParserCallable()) {
            $fakeNode = new EmptyNode($line);
            $fakeNode->setSourceContext($parser->getStream()->getSourceContext());

            return ($function->getParserCallable())($parser->getParser(), $fakeNode, $args, $line);
        }

        if (!isset($this->readyNodes[$class = $function->getNodeClass()])) {
            $this->readyNodes[$class] = (bool) (new \ReflectionClass($class))->getConstructor()->getAttributes(FirstClassTwigCallableReady::class);
        }

        if (!$ready = $this->readyNodes[$class]) {
            trigger_deprecation('twig/twig', '3.12', 'Twig node "%s" is not marked as ready for passing a "TwigFunction" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.', $class);
        }

        return new $class($ready ? $function : $function->getName(), $args, $line);
    }

    public function getOperator(): string
    {
        return '(';
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

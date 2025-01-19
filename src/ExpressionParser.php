<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig;

use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\Binary\ConcatBinary;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Unary\NegUnary;
use Twig\Node\Expression\Unary\PosUnary;
use Twig\Node\Expression\Unary\SpreadUnary;
use Twig\Node\Expression\Variable\AssignContextVariable;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Expression\Variable\LocalVariable;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\Operator\OperatorArity;
use Twig\Operator\Operators;

/**
 * Parses expressions.
 *
 * This parser implements a "Precedence climbing" algorithm.
 *
 * @see https://www.engr.mun.ca/~theo/Misc/exp_parsing.htm
 * @see https://en.wikipedia.org/wiki/Operator-precedence_parser
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExpressionParser
{
    /**
     * @deprecated since Twig 3.20
     */
    public const OPERATOR_LEFT = 1;
    /**
     * @deprecated since Twig 3.20
     */
    public const OPERATOR_RIGHT = 2;

    private Operators $operators;
    private bool $deprecationCheck = true;

    public function __construct(
        private Parser $parser,
        private Environment $env,
    ) {
        $this->operators = $env->getOperators();
    }

    /**
     * @internal
     */
    public function getParser(): Parser
    {
        return $this->parser;
    }

    /**
     * @internal
     */
    public function getStream(): TokenStream
    {
        return $this->parser->getStream();
    }

    /**
     * @internal
     */
    public function getImportedSymbol(string $type, string $name)
    {
        return $this->parser->getImportedSymbol($type, $name);
    }

    public function parseExpression($precedence = 0)
    {
        if (\func_num_args() > 1) {
            trigger_deprecation('twig/twig', '3.15', 'Passing a second argument ($allowArrow) to "%s()" is deprecated.', __METHOD__);
        }

        $expr = $this->parsePrimary();
        $token = $this->parser->getCurrentToken();
        while (
            $token->test(Token::OPERATOR_TYPE)
            && (
                ($op = $this->operators->getTernary($token->getValue())) && $op->getPrecedence() >= $precedence
                || ($op = $this->operators->getBinary($token->getValue())) && $op->getPrecedence() >= $precedence
            )
        ) {
            $this->parser->getStream()->next();
            $previous = $this->setDeprecationCheck(true);
            try {
                $expr = $op->parse($this, $expr, $token);
            } finally {
                $this->setDeprecationCheck($previous);
            }
            $expr->setAttribute('operator', $op);
            $this->triggerPrecedenceDeprecations($expr);
            $token = $this->parser->getCurrentToken();
        }

        return $expr;
    }

    private function triggerPrecedenceDeprecations(AbstractExpression $expr): void
    {
        $precedenceChanges = $this->operators->getPrecedenceChanges();
        // Check that the all nodes that are between the 2 precedences have explicit parentheses
        if (!$expr->hasAttribute('operator') || !isset($precedenceChanges[$expr->getAttribute('operator')])) {
            return;
        }

        if (OperatorArity::Unary === $expr->getAttribute('operator')->getArity()) {
            if ($expr->hasExplicitParentheses()) {
                return;
            }
            $operator = $expr->getAttribute('operator');
            /** @var AbstractExpression $node */
            $node = $expr->getNode('node');
            foreach ($precedenceChanges as $op => $changes) {
                if (!\in_array($operator, $changes, true)) {
                    continue;
                }
                if ($node->hasAttribute('operator') && $op === $node->getAttribute('operator')) {
                    $change = $operator->getPrecedenceChange();
                    trigger_deprecation($change->getPackage(), $change->getVersion(), \sprintf('Add explicit parentheses around the "%s" unary operator to avoid behavior change in the next major version as its precedence will change in "%s" at line %d.', $operator->getOperator(), $this->parser->getStream()->getSourceContext()->getName(), $node->getTemplateLine()));
                }
            }
        } else {
            foreach ($precedenceChanges[$expr->getAttribute('operator')] as $operator) {
                foreach ($expr as $node) {
                    /** @var AbstractExpression $node */
                    if ($node->hasAttribute('operator') && $operator === $node->getAttribute('operator') && !$node->hasExplicitParentheses()) {
                        $change = $operator->getPrecedenceChange();
                        trigger_deprecation($change->getPackage(), $change->getVersion(), \sprintf('Add explicit parentheses around the "%s" binary operator to avoid behavior change in the next major version as its precedence will change in "%s" at line %d.', $operator->getOperator(), $this->parser->getStream()->getSourceContext()->getName(), $node->getTemplateLine()));
                    }
                }
            }
        }
    }

    /**
     * @internal
     */
    public function parsePrimary(): AbstractExpression
    {
        $token = $this->parser->getCurrentToken();
        if ($token->test(Token::OPERATOR_TYPE) && $operator = $this->operators->getUnary($token->getValue())) {
            $this->parser->getStream()->next();
            $previous = $this->setDeprecationCheck(false);
            try {
                $expr = $operator->parse($this, $token);
            } finally {
                $this->setDeprecationCheck($previous);
            }
            $expr->setAttribute('operator', $operator);

            if ($this->deprecationCheck) {
                $this->triggerPrecedenceDeprecations($expr);
            }

            return $expr;
        }

        return $this->parsePrimaryExpression();
    }

    public function parsePrimaryExpression()
    {
        $token = $this->parser->getCurrentToken();
        switch (true) {
            case $token->test(Token::NAME_TYPE):
                $this->parser->getStream()->next();
                switch ($token->getValue()) {
                    case 'true':
                    case 'TRUE':
                        return new ConstantExpression(true, $token->getLine());

                    case 'false':
                    case 'FALSE':
                        return new ConstantExpression(false, $token->getLine());

                    case 'none':
                    case 'NONE':
                    case 'null':
                    case 'NULL':
                        return new ConstantExpression(null, $token->getLine());

                    default:
                        return new ContextVariable($token->getValue(), $token->getLine());
                }

                // no break
            case $token->test(Token::NUMBER_TYPE):
                $this->parser->getStream()->next();

                return new ConstantExpression($token->getValue(), $token->getLine());

            case $token->test(Token::STRING_TYPE):
            case $token->test(Token::INTERPOLATION_START_TYPE):
                return $this->parseStringExpression();

            case $token->test(Token::PUNCTUATION_TYPE):
                // In 4.0, we should always return the node or throw an error for default
                if ($node = match ($token->getValue()) {
                    '{' => $this->parseMappingExpression(),
                    default => null,
                }) {
                    return $node;
                }

                // no break
            case $token->test(Token::OPERATOR_TYPE):
                if ('[' === $token->getValue()) {
                    return $this->parseSequenceExpression();
                }

                if (preg_match(Lexer::REGEX_NAME, $token->getValue(), $matches) && $matches[0] == $token->getValue()) {
                    // in this context, string operators are variable names
                    $this->parser->getStream()->next();

                    return new ContextVariable($token->getValue(), $token->getLine());
                }

                if ('=' === $token->getValue() && ('==' === $this->parser->getStream()->look(-1)->getValue() || '!=' === $this->parser->getStream()->look(-1)->getValue())) {
                    throw new SyntaxError(\sprintf('Unexpected operator of value "%s". Did you try to use "===" or "!==" for strict comparison? Use "is same as(value)" instead.', $token->getValue()), $token->getLine(), $this->parser->getStream()->getSourceContext());
                }

                // no break
            default:
                throw new SyntaxError(\sprintf('Unexpected token "%s" of value "%s".', $token->toEnglish(), $token->getValue()), $token->getLine(), $this->parser->getStream()->getSourceContext());
        }
    }

    public function parseStringExpression()
    {
        $stream = $this->parser->getStream();

        $nodes = [];
        // a string cannot be followed by another string in a single expression
        $nextCanBeString = true;
        while (true) {
            if ($nextCanBeString && $token = $stream->nextIf(Token::STRING_TYPE)) {
                $nodes[] = new ConstantExpression($token->getValue(), $token->getLine());
                $nextCanBeString = false;
            } elseif ($stream->nextIf(Token::INTERPOLATION_START_TYPE)) {
                $nodes[] = $this->parseExpression();
                $stream->expect(Token::INTERPOLATION_END_TYPE);
                $nextCanBeString = true;
            } else {
                break;
            }
        }

        $expr = array_shift($nodes);
        foreach ($nodes as $node) {
            $expr = new ConcatBinary($expr, $node, $node->getTemplateLine());
        }

        return $expr;
    }

    /**
     * @deprecated since Twig 3.11, use parseSequenceExpression() instead
     */
    public function parseArrayExpression()
    {
        trigger_deprecation('twig/twig', '3.11', 'Calling "%s()" is deprecated, use "parseSequenceExpression()" instead.', __METHOD__);

        return $this->parseSequenceExpression();
    }

    public function parseSequenceExpression()
    {
        $stream = $this->parser->getStream();
        $stream->expect(Token::OPERATOR_TYPE, '[', 'A sequence element was expected');

        $node = new ArrayExpression([], $stream->getCurrent()->getLine());
        $first = true;
        while (!$stream->test(Token::PUNCTUATION_TYPE, ']')) {
            if (!$first) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'A sequence element must be followed by a comma');

                // trailing ,?
                if ($stream->test(Token::PUNCTUATION_TYPE, ']')) {
                    break;
                }
            }
            $first = false;

            if ($stream->nextIf(Token::SPREAD_TYPE)) {
                $expr = $this->parseExpression();
                $expr->setAttribute('spread', true);
                $node->addElement($expr);
            } else {
                $node->addElement($this->parseExpression());
            }
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ']', 'An opened sequence is not properly closed');

        return $node;
    }

    /**
     * @deprecated since Twig 3.11, use parseMappingExpression() instead
     */
    public function parseHashExpression()
    {
        trigger_deprecation('twig/twig', '3.11', 'Calling "%s()" is deprecated, use "parseMappingExpression()" instead.', __METHOD__);

        return $this->parseMappingExpression();
    }

    public function parseMappingExpression()
    {
        $stream = $this->parser->getStream();
        $stream->expect(Token::PUNCTUATION_TYPE, '{', 'A mapping element was expected');

        $node = new ArrayExpression([], $stream->getCurrent()->getLine());
        $first = true;
        while (!$stream->test(Token::PUNCTUATION_TYPE, '}')) {
            if (!$first) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'A mapping value must be followed by a comma');

                // trailing ,?
                if ($stream->test(Token::PUNCTUATION_TYPE, '}')) {
                    break;
                }
            }
            $first = false;

            if ($stream->nextIf(Token::SPREAD_TYPE)) {
                $value = $this->parseExpression();
                $value->setAttribute('spread', true);
                $node->addElement($value);
                continue;
            }

            // a mapping key can be:
            //
            //  * a number -- 12
            //  * a string -- 'a'
            //  * a name, which is equivalent to a string -- a
            //  * an expression, which must be enclosed in parentheses -- (1 + 2)
            if ($token = $stream->nextIf(Token::NAME_TYPE)) {
                $key = new ConstantExpression($token->getValue(), $token->getLine());

                // {a} is a shortcut for {a:a}
                if ($stream->test(Token::PUNCTUATION_TYPE, [',', '}'])) {
                    $value = new ContextVariable($key->getAttribute('value'), $key->getTemplateLine());
                    $node->addElement($value, $key);
                    continue;
                }
            } elseif (($token = $stream->nextIf(Token::STRING_TYPE)) || $token = $stream->nextIf(Token::NUMBER_TYPE)) {
                $key = new ConstantExpression($token->getValue(), $token->getLine());
            } elseif ($stream->test(Token::OPERATOR_TYPE, '(')) {
                $key = $this->parseExpression();
            } else {
                $current = $stream->getCurrent();

                throw new SyntaxError(\sprintf('A mapping key must be a quoted string, a number, a name, or an expression enclosed in parentheses (unexpected token "%s" of value "%s".', $current->toEnglish(), $current->getValue()), $current->getLine(), $stream->getSourceContext());
            }

            $stream->expect(Token::PUNCTUATION_TYPE, ':', 'A mapping key must be followed by a colon (:)');
            $value = $this->parseExpression();

            $node->addElement($value, $key);
        }
        $stream->expect(Token::PUNCTUATION_TYPE, '}', 'An opened mapping is not properly closed');

        return $node;
    }

    /**
     * @deprecated since Twig 3.20
     */
    public function parsePostfixExpression($node)
    {
        trigger_deprecation('twig/twig', '3.20', 'The "%s()" method is deprecated.', __METHOD__);

        while (true) {
            $token = $this->parser->getCurrentToken();
            if ($token->test(Token::PUNCTUATION_TYPE)) {
                if ('.' == $token->getValue() || '[' == $token->getValue()) {
                    $node = $this->parseSubscriptExpression($node);
                } elseif ('|' == $token->getValue()) {
                    $node = $this->parseFilterExpression($node);
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        return $node;
    }

    /**
     * @deprecated since Twig 3.20
     */
    public function parseSubscriptExpression($node)
    {
        trigger_deprecation('twig/twig', '3.20', 'The "%s()" method is deprecated.', __METHOD__);

        if ('.' === $this->parser->getStream()->next()->getValue()) {
            return $this->operators->getBinary('.')->parse($this, $node, $this->parser->getCurrentToken());
        }

        return $this->operators->getBinary('[')->parse($this, $node, $this->parser->getCurrentToken());
    }

    /**
     * @deprecated since Twig 3.20
     */
    public function parseFilterExpression($node)
    {
        trigger_deprecation('twig/twig', '3.20', 'The "%s()" method is deprecated.', __METHOD__);

        $this->parser->getStream()->next();

        return $this->parseFilterExpressionRaw($node);
    }

    /**
     * @deprecated since Twig 3.20
     */
    public function parseFilterExpressionRaw($node)
    {
        trigger_deprecation('twig/twig', '3.20', 'The "%s()" method is deprecated.', __METHOD__);

        $op = $this->operators->getBinary('|');
        while (true) {
            $node = $op->parse($this, $node, $this->parser->getCurrentToken());
            if (!$this->parser->getStream()->test(Token::OPERATOR_TYPE, '|')) {
                break;
            }
            $this->parser->getStream()->next();
        }

        return $node;
    }

    /**
     * Parses arguments.
     *
     * @return Node
     *
     * @throws SyntaxError
     *
     * @deprecated since Twig 3.19 Use parseNamedArguments() instead
     */
    public function parseArguments()
    {
        trigger_deprecation('twig/twig', '3.19', \sprintf('The "%s()" method is deprecated, use "%s::parseNamedArguments()" instead.', __METHOD__, __CLASS__));

        $namedArguments = false;
        $definition = false;
        if (\func_num_args() > 1) {
            $definition = func_get_arg(1);
        }
        if (\func_num_args() > 0) {
            trigger_deprecation('twig/twig', '3.15', 'Passing arguments to "%s()" is deprecated.', __METHOD__);
            $namedArguments = func_get_arg(0);
        }

        $args = [];
        $stream = $this->parser->getStream();

        $stream->expect(Token::OPERATOR_TYPE, '(', 'A list of arguments must begin with an opening parenthesis');
        $hasSpread = false;
        while (!$stream->test(Token::PUNCTUATION_TYPE, ')')) {
            if ($args) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');

                // if the comma above was a trailing comma, early exit the argument parse loop
                if ($stream->test(Token::PUNCTUATION_TYPE, ')')) {
                    break;
                }
            }

            if ($definition) {
                $token = $stream->expect(Token::NAME_TYPE, null, 'An argument must be a name');
                $value = new ContextVariable($token->getValue(), $this->parser->getCurrentToken()->getLine());
            } else {
                if ($stream->nextIf(Token::SPREAD_TYPE)) {
                    $hasSpread = true;
                    $value = new SpreadUnary($this->parseExpression(), $stream->getCurrent()->getLine());
                } elseif ($hasSpread) {
                    throw new SyntaxError('Normal arguments must be placed before argument unpacking.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
                } else {
                    $value = $this->parseExpression();
                }
            }

            $name = null;
            if ($namedArguments && (($token = $stream->nextIf(Token::OPERATOR_TYPE, '=')) || (!$definition && $token = $stream->nextIf(Token::PUNCTUATION_TYPE, ':')))) {
                if (!$value instanceof ContextVariable) {
                    throw new SyntaxError(\sprintf('A parameter name must be a string, "%s" given.', \get_class($value)), $token->getLine(), $stream->getSourceContext());
                }
                $name = $value->getAttribute('name');

                if ($definition) {
                    $value = $this->parsePrimary();

                    if (!$this->checkConstantExpression($value)) {
                        throw new SyntaxError('A default value for an argument must be a constant (a boolean, a string, a number, a sequence, or a mapping).', $token->getLine(), $stream->getSourceContext());
                    }
                } else {
                    $value = $this->parseExpression();
                }
            }

            if ($definition) {
                if (null === $name) {
                    $name = $value->getAttribute('name');
                    $value = new ConstantExpression(null, $this->parser->getCurrentToken()->getLine());
                    $value->setAttribute('is_implicit', true);
                }
                $args[$name] = $value;
            } else {
                if (null === $name) {
                    $args[] = $value;
                } else {
                    $args[$name] = $value;
                }
            }
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');

        return new Nodes($args);
    }

    /**
     * @deprecated since Twig 3.20, use "AbstractTokenParser::parseAssignmentExpression()" instead
     */
    public function parseAssignmentExpression()
    {
        trigger_deprecation('twig/twig', '3.20', 'The "%s()" method is deprecated, use "AbstractTokenParser::parseAssignmentExpression()" instead.', __METHOD__);

        $stream = $this->parser->getStream();
        $targets = [];
        while (true) {
            $token = $this->parser->getCurrentToken();
            if ($stream->test(Token::OPERATOR_TYPE) && preg_match(Lexer::REGEX_NAME, $token->getValue())) {
                // in this context, string operators are variable names
                $this->parser->getStream()->next();
            } else {
                $stream->expect(Token::NAME_TYPE, null, 'Only variables can be assigned to');
            }
            $targets[] = new AssignContextVariable($token->getValue(), $token->getLine());

            if (!$stream->nextIf(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        }

        return new Nodes($targets);
    }

    /**
     * @deprecated since Twig 3.20
     */
    public function parseMultitargetExpression()
    {
        trigger_deprecation('twig/twig', '3.20', 'The "%s()" method is deprecated.', __METHOD__);

        $targets = [];
        while (true) {
            $targets[] = $this->parseExpression();
            if (!$this->parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, ',')) {
                break;
            }
        }

        return new Nodes($targets);
    }

    public function getTest(int $line): TwigTest
    {
        return $this->parser->getTest($line);
    }

    public function getFunction(string $name, int $line): TwigFunction
    {
        return $this->parser->getFunction($name, $line);
    }

    public function getFilter(string $name, int $line): TwigFilter
    {
        return $this->parser->getFilter($name, $line);
    }

    // checks that the node only contains "constant" elements
    // to be removed in 4.0
    private function checkConstantExpression(Node $node): bool
    {
        if (!($node instanceof ConstantExpression || $node instanceof ArrayExpression
            || $node instanceof NegUnary || $node instanceof PosUnary
        )) {
            return false;
        }

        foreach ($node as $n) {
            if (!$this->checkConstantExpression($n)) {
                return false;
            }
        }

        return true;
    }

    private function setDeprecationCheck(bool $deprecationCheck): bool
    {
        $current = $this->deprecationCheck;
        $this->deprecationCheck = $deprecationCheck;

        return $current;
    }

    /**
     * @internal
     */
    public function parseCallableArguments(int $line, bool $parseOpenParenthesis = true): ArrayExpression
    {
        $arguments = new ArrayExpression([], $line);
        foreach ($this->parseNamedArguments($parseOpenParenthesis) as $k => $n) {
            $arguments->addElement($n, new LocalVariable($k, $line));
        }

        return $arguments;
    }

    /**
     * @deprecated since Twig 3.19 Use parseNamedArguments() instead
     */
    public function parseOnlyArguments()
    {
        trigger_deprecation('twig/twig', '3.19', \sprintf('The "%s()" method is deprecated, use "%s::parseNamedArguments()" instead.', __METHOD__, __CLASS__));

        return $this->parseNamedArguments();
    }

    public function parseNamedArguments(bool $parseOpenParenthesis = true): Nodes
    {
        $args = [];
        $stream = $this->parser->getStream();
        if ($parseOpenParenthesis) {
            $stream->expect(Token::OPERATOR_TYPE, '(', 'A list of arguments must begin with an opening parenthesis');
        }
        $hasSpread = false;
        while (!$stream->test(Token::PUNCTUATION_TYPE, ')')) {
            if ($args) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');

                // if the comma above was a trailing comma, early exit the argument parse loop
                if ($stream->test(Token::PUNCTUATION_TYPE, ')')) {
                    break;
                }
            }

            if ($stream->nextIf(Token::SPREAD_TYPE)) {
                $hasSpread = true;
                $value = new SpreadUnary($this->parseExpression(), $stream->getCurrent()->getLine());
            } elseif ($hasSpread) {
                throw new SyntaxError('Normal arguments must be placed before argument unpacking.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            } else {
                $value = $this->parseExpression();
            }

            $name = null;
            if (($token = $stream->nextIf(Token::OPERATOR_TYPE, '=')) || ($token = $stream->nextIf(Token::PUNCTUATION_TYPE, ':'))) {
                if (!$value instanceof ContextVariable) {
                    throw new SyntaxError(\sprintf('A parameter name must be a string, "%s" given.', \get_class($value)), $token->getLine(), $stream->getSourceContext());
                }
                $name = $value->getAttribute('name');
                $value = $this->parseExpression();
            }

            if (null === $name) {
                $args[] = $value;
            } else {
                $args[$name] = $value;
            }
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');

        return new Nodes($args);
    }
}

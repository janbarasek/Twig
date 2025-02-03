<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\ExpressionParser;

/**
 * @template-implements \IteratorAggregate<ExpressionParserInterface>
 *
 * @internal
 */
final class ExpressionParsers implements \IteratorAggregate
{
    /**
     * @var array<value-of<ExpressionParserType>, array<string, ExpressionParserInterface>>
     */
    private array $parsers = [];

    /**
     * @var array<value-of<ExpressionParserType>, array<class-string<ExpressionParserInterface>, ExpressionParserInterface>>
     */
    private array $parsersByClass = [];

    /**
     * @var array<value-of<ExpressionParserType>, array<string, ExpressionParserInterface>>
     */
    private array $aliases = [];

    /**
     * @var \WeakMap<ExpressionParserInterface, array<ExpressionParserInterface>>|null
     */
    private ?\WeakMap $precedenceChanges = null;

    /**
     * @param array<ExpressionParserInterface> $parsers
     */
    public function __construct(
        array $parsers = [],
    ) {
        $this->precedenceChanges = null;
        $this->add($parsers);
    }

    /**
     * @param array<ExpressionParserInterface> $parsers
     *
     * @return $this
     */
    public function add(array $parsers): self
    {
        foreach ($parsers as $operator) {
            $type = ExpressionParserType::getType($operator);
            $this->parsers[$type->value][$operator->getName()] = $operator;
            $this->parsersByClass[$type->value][get_class($operator)] = $operator;
            foreach ($operator->getAliases() as $alias) {
                $this->aliases[$type->value][$alias] = $operator;
            }
        }

        return $this;
    }

    /**
     * @param class-string<PrefixExpressionParserInterface> $name
     */
    public function getPrefixByClass(string $name): ?PrefixExpressionParserInterface
    {
        return $this->parsersByClass[ExpressionParserType::Prefix->value][$name] ?? null;
    }

    public function getPrefix(string $name): ?PrefixExpressionParserInterface
    {
        return
            $this->parsers[ExpressionParserType::Prefix->value][$name]
            ?? $this->aliases[ExpressionParserType::Prefix->value][$name]
            ?? null
        ;
    }

    /**
     * @param class-string<InfixExpressionParserInterface> $name
     */
    public function getInfixByClass(string $name): ?InfixExpressionParserInterface
    {
        return $this->parsersByClass[ExpressionParserType::Infix->value][$name] ?? null;
    }

    public function getInfix(string $name): ?InfixExpressionParserInterface
    {
        return
            $this->parsers[ExpressionParserType::Infix->value][$name]
            ?? $this->aliases[ExpressionParserType::Infix->value][$name]
            ?? null
        ;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->parsers as $parsers) {
            // we don't yield the keys
            yield from $parsers;
        }
    }

    /**
     * @internal
     *
     * @return \WeakMap<ExpressionParserInterface, array<ExpressionParserInterface>>
     */
    public function getPrecedenceChanges(): \WeakMap
    {
        if (null === $this->precedenceChanges) {
            $this->precedenceChanges = new \WeakMap();
            foreach ($this as $ep) {
                if (!$ep->getPrecedenceChange()) {
                    continue;
                }
                $min = min($ep->getPrecedenceChange()->getNewPrecedence(), $ep->getPrecedence());
                $max = max($ep->getPrecedenceChange()->getNewPrecedence(), $ep->getPrecedence());
                foreach ($this as $e) {
                    if ($e->getPrecedence() > $min && $e->getPrecedence() < $max) {
                        if (!isset($this->precedenceChanges[$e])) {
                            $this->precedenceChanges[$e] = [];
                        }
                        $this->precedenceChanges[$e][] = $ep;
                    }
                }
            }
        }

        return $this->precedenceChanges;
    }
}

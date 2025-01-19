<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Operator;

use Twig\Operator\Binary\AbstractBinaryOperator;
use Twig\Operator\Unary\AbstractUnaryOperator;

class Operators implements \IteratorAggregate
{
    private array $operators = [];
    private array $aliases = [];
    private ?\WeakMap $precedenceChanges = null;

    public function __construct(
        array $operators = [],
    ) {
        $this->add($operators);
    }

    /**
     * @param array<AbstractOperator> $operators
     *
     * @return $this
     */
    public function add(array $operators): self
    {
        $this->precedenceChanges = null;
        foreach ($operators as $operator) {
            $this->operators[$operator->getArity()->value][$operator->getOperator()] = $operator;
            foreach ($operator->getAliases() as $alias) {
                $this->aliases[$operator->getArity()->value][$alias] = $operator;
            }
        }

        return $this;
    }

    public function getUnary(string $name): ?AbstractUnaryOperator
    {
        return $this->operators[OperatorArity::Unary->value][$name] ?? ($this->aliases[OperatorArity::Unary->value][$name] ?? null);
    }

    public function getBinary(string $name): ?AbstractBinaryOperator
    {
        return $this->operators[OperatorArity::Binary->value][$name] ?? ($this->aliases[OperatorArity::Binary->value][$name] ?? null);
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->operators as $operators) {
            // we don't yield the keys
            yield from $operators;
        }
    }

    /**
     * @internal
     *
     * @return \WeakMap<AbstractOperator, array<AbstractOperator>>
     */
    public function getPrecedenceChanges(): \WeakMap
    {
        if (null === $this->precedenceChanges) {
            $this->precedenceChanges = new \WeakMap();
            foreach ($this as $op) {
                if (!$op->getPrecedenceChange()) {
                    continue;
                }
                $min = min($op->getPrecedenceChange()->getNewPrecedence(), $op->getPrecedence());
                $max = max($op->getPrecedenceChange()->getNewPrecedence(), $op->getPrecedence());
                foreach ($this as $o) {
                    if ($o->getPrecedence() > $min && $o->getPrecedence() < $max) {
                        if (!isset($this->precedenceChanges[$o])) {
                            $this->precedenceChanges[$o] = [];
                        }
                        $this->precedenceChanges[$o][] = $op;
                    }
                }
            }
        }

        return $this->precedenceChanges;
    }
}

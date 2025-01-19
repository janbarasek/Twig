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

use Twig\OperatorPrecedenceChange;

interface OperatorInterface
{
    public function __toString(): string;

    public function getOperator(): string;

    public function getArity(): OperatorArity;

    public function getPrecedence(): int;

    public function getPrecedenceChange(): ?OperatorPrecedenceChange;

    /**
     * @return array<string>
     */
    public function getAliases(): array;
}

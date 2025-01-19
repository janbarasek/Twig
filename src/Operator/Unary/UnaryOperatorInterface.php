<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Twig\Operator\Unary;

use Twig\ExpressionParser;
use Twig\Node\Expression\AbstractExpression;
use Twig\Operator\OperatorInterface;
use Twig\Token;

interface UnaryOperatorInterface extends OperatorInterface
{
    public function parse(ExpressionParser $parser, Token $token): AbstractExpression;
}

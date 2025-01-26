<?php

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Operator\OperatorArity;
use Twig\Operator\OperatorAssociativity;

require_once dirname(__DIR__).'/vendor/autoload.php';

function printOperators($output, array $operators, bool $withAssociativity = false)
{
    if ($withAssociativity) {
        fwrite($output, "\n=========== =========== =============\n");
        fwrite($output, "Precedence  Operator    Associativity\n");
        fwrite($output, "=========== =========== =============\n");
    } else {
        fwrite($output, "\n=========== ===========\n");
        fwrite($output, "Precedence  Operator\n");
        fwrite($output, "=========== ===========\n");
    }

    usort($operators, function($a, $b) {
        $aPrecedence = $a->getPrecedenceChange() ? $a->getPrecedenceChange()->getNewPrecedence() : $a->getPrecedence();
        $bPrecedence = $b->getPrecedenceChange() ? $b->getPrecedenceChange()->getNewPrecedence() : $b->getPrecedence();
        return $bPrecedence - $aPrecedence; 
    });

    $current = \PHP_INT_MAX;
    foreach ($operators as $operator) {
        $precedence = $operator->getPrecedenceChange() ? $operator->getPrecedenceChange()->getNewPrecedence() : $operator->getPrecedence();
        if ($precedence !== $current) {
            $current = $precedence;
            if ($withAssociativity) {
                fwrite($output, \sprintf("\n%-11d %-11s %s", $precedence, $operator->getOperator(), OperatorAssociativity::Left === $operator->getAssociativity() ? 'Left' : 'Right'));
            } else {
                fwrite($output, \sprintf("\n%-11d %s", $precedence, $operator->getOperator()));
            }
        } else {
            fwrite($output, "\n".str_repeat(' ', 12).$operator->getOperator());
        }
    }
    fwrite($output, "\n");
}

$output = fopen(dirname(__DIR__).'/doc/operators_precedence.rst', 'w');

$twig = new Environment(new ArrayLoader([]));
$unaryOperators = [];
$notUnaryOperators = [];
foreach ($twig->getOperators() as $operator) {
    if ($operator->getArity()->value == OperatorArity::Unary->value) {
        $unaryOperators[] = $operator;
    } else {
        $notUnaryOperators[] = $operator;
    }
}

fwrite($output, "Unary operators precedence:\n");
printOperators($output, $unaryOperators);

fwrite($output, "\nBinary and Ternary operators precedence:\n");
printOperators($output, $notUnaryOperators, true);

fclose($output);

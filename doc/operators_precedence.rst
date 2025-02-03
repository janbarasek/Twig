Unary operators precedence:

=========== ===========
Precedence  Operator
=========== ===========

500         -
            +
70          not
0           (
            literal

Binary and Ternary operators precedence:

=========== =========== =============
Precedence  Operator    Associativity
=========== =========== =============

300         .           Left
            [
            |
            (
250         =>          Left
200         **          Right
100         is          Left
            is not
60          *           Left
            /
            //
            %
30          +           Left
            -
27          ~           Left
25          ..          Left
20          ==          Left
            !=
            <=>
            <
            >
            >=
            <=
            not in
            in
            matches
            starts with
            ends with
            has some
            has every
18          b-and       Left
17          b-xor       Left
16          b-or        Left
15          and         Left
12          xor         Left
10          or          Left
5           ?:          Right
            ??
0           ?           Left

<?php

declare(strict_types=1);

use mal\reader\Scanner;

test('read of numbers', function () {
    expect(Scanner::tokenize('1'))->toEqual(['1']);
    expect(Scanner::tokenize('7'))->toEqual(['7']);
    expect(Scanner::tokenize('  7'))->toEqual(['7']);
    expect(Scanner::tokenize('-123'))->toEqual(['-123']);
});

test('read of symbols', function () {
    expect(Scanner::tokenize('+'))->toEqual(['+']);
    expect(Scanner::tokenize('abc'))->toEqual(['abc']);
    expect(Scanner::tokenize('abc5'))->toEqual(['abc5']);
    expect(Scanner::tokenize('abc-def'))->toEqual(['abc-def']);
});

test('non-numbers starting with a dash', function () {
    expect(Scanner::tokenize('-'))->toEqual(['-']);
    expect(Scanner::tokenize('-abc'))->toEqual(['-abc']);
    expect(Scanner::tokenize('->>'))->toEqual(['->>']);
});

test('read of lists', function () {
    expect(Scanner::tokenize('(+ 1 2)'))->toEqual(['(', '+', '1', '2', ')']);
    expect(Scanner::tokenize('()'))->toEqual(['(', ')']);
    expect(Scanner::tokenize('( )'))->toEqual(['(', ')']);
    expect(Scanner::tokenize('(nil)'))->toEqual(['(', 'nil', ')']);
    expect(Scanner::tokenize('((3 4))'))->toEqual(['(', '(', '3', '4', ')', ')']);
    expect(Scanner::tokenize('(+ 1 (+ 2 3))'))->toEqual(['(', '+', '1', '(', '+', '2', '3', ')', ')']);
    expect(Scanner::tokenize('( +   1   (+   2 3   )   )'))->toEqual(['(', '+', '1', '(', '+', '2', '3', ')', ')']);
    expect(Scanner::tokenize('(* 1 2)'))->toEqual(['(', '*', '1', '2', ')']);
    expect(Scanner::tokenize('(** 1 2)'))->toEqual(['(', '**', '1', '2', ')']);
    expect(Scanner::tokenize('(* -3 6)'))->toEqual(['(', '*', '-3', '6', ')']);
    expect(Scanner::tokenize('(()())'))->toEqual(['(', '(', ')', '(', ')', ')']);
});

test('commas as whitespace', function () {
    expect(Scanner::tokenize('(1 2, 3,,,,),,'))->toEqual(['(', '1', '2', '3', ')']);
});

test('read of nil/true/false', function () {
    expect(Scanner::tokenize('nil'))->toEqual(['nil']);
    expect(Scanner::tokenize('true'))->toEqual(['true']);
    expect(Scanner::tokenize('false'))->toEqual(['false']);
});

test('read of strings', function () {
    expect(Scanner::tokenize('"abc"'))->toEqual(['"abc"']);
    expect(Scanner::tokenize('   "abc"'))->toEqual(['"abc"']);
    expect(Scanner::tokenize('"abc (with parens)"'))->toEqual(['"abc (with parens)"']);
    expect(Scanner::tokenize('"abc\"def"'))->toEqual(['"abc\"def"']);
    expect(Scanner::tokenize('""'))->toEqual(['""']);

    $s = <<<'TXT'
        "\\"
        TXT;
    expect(Scanner::tokenize($s))->toEqual([$s]);

    $s = <<<'TXT'
        "\\\\\\\\\\\\\\\\\\"
        TXT;
    expect(Scanner::tokenize($s))->toEqual([$s]);

    expect(Scanner::tokenize('"&"'))->toEqual(['"&"']);
    expect(Scanner::tokenize('"\'"'))->toEqual(['"\'"']);
    expect(Scanner::tokenize('"("'))->toEqual(['"("']);
    expect(Scanner::tokenize('")"'))->toEqual(['")"']);
    expect(Scanner::tokenize('"*"'))->toEqual(['"*"']);
    expect(Scanner::tokenize('"+"'))->toEqual(['"+"']);
    expect(Scanner::tokenize('","'))->toEqual(['","']);
    expect(Scanner::tokenize('"-"'))->toEqual(['"-"']);
    expect(Scanner::tokenize('"/"'))->toEqual(['"/"']);
    expect(Scanner::tokenize('":"'))->toEqual(['":"']);
    expect(Scanner::tokenize('";"'))->toEqual(['";"']);
    expect(Scanner::tokenize('"<"'))->toEqual(['"<"']);
    expect(Scanner::tokenize('"="'))->toEqual(['"="']);
    expect(Scanner::tokenize('">"'))->toEqual(['">"']);
    expect(Scanner::tokenize('"?"'))->toEqual(['"?"']);
    expect(Scanner::tokenize('"@"'))->toEqual(['"@"']);
    expect(Scanner::tokenize('"["'))->toEqual(['"["']);
    expect(Scanner::tokenize('"]"'))->toEqual(['"]"']);
    expect(Scanner::tokenize('"^"'))->toEqual(['"^"']);
    expect(Scanner::tokenize('"_"'))->toEqual(['"_"']);
    expect(Scanner::tokenize('"`"'))->toEqual(['"`"']);
    expect(Scanner::tokenize('"{"'))->toEqual(['"{"']);
    expect(Scanner::tokenize('"}"'))->toEqual(['"}"']);
    expect(Scanner::tokenize('"~"'))->toEqual(['"~"']);
    expect(Scanner::tokenize('"!"'))->toEqual(['"!"']);
});

test('keywords', function () {
    expect(Scanner::tokenize(':kw'))->toEqual([':kw']);
    expect(Scanner::tokenize('(:kw1 :kw2 :kw3)'))->toEqual(['(', ':kw1', ':kw2', ':kw3', ')']);
});

test('read of vectors', function () {
    expect(Scanner::tokenize('[]'))->toEqual(['[', ']']);
    expect(Scanner::tokenize('[  ]'))->toEqual(['[', ']']);
    expect(Scanner::tokenize('[+1 2]'))->toEqual(['[', '+1', '2', ']']);
    expect(Scanner::tokenize('[[3 4]]'))->toEqual(['[', '[', '3', '4', ']', ']']);
    expect(Scanner::tokenize('[+1 [+2 3]]'))->toEqual(['[', '+1', '[', '+2', '3', ']', ']']);
    expect(Scanner::tokenize('[+1   [+2 3   ]   ]'))->toEqual(['[', '+1', '[', '+2', '3', ']', ']']);
    expect(Scanner::tokenize('([])'))->toEqual(['(', '[', ']', ')']);
});

test('read of hash maps', function () {
    expect(Scanner::tokenize('{}'))->toEqual(['{', '}']);
    expect(Scanner::tokenize('{ }'))->toEqual(['{', '}']);
    expect(Scanner::tokenize('{"abc" 1}'))->toEqual(['{', '"abc"', "1", '}']);
    expect(Scanner::tokenize('{"a" {"b" 2}}'))->toEqual(['{', '"a"', '{', '"b"', '2', '}', '}']);
    expect(Scanner::tokenize('{"a" {"b" {"c" 3}}}'))->toEqual(['{', '"a"', '{', '"b"', '{', '"c"', '3', '}', '}', '}']);
    expect(Scanner::tokenize('{  "a"  {"b"   {  "cde"     3   }  }}'))->toEqual(['{', '"a"', '{', '"b"', '{', '"cde"', '3', '}', '}', '}']);
    expect(Scanner::tokenize('{"a1" 1 "a2" 2 "a3" 3}'))->toEqual(['{', '"a1"', '1', '"a2"', '2', '"a3"', '3', '}']);
    expect(Scanner::tokenize('{  :a  {:b   {  :cde     3   }  }}'))->toEqual(['{', ':a', '{', ':b', '{', ':cde', '3', '}', '}', '}']);
    expect(Scanner::tokenize('{"1" 1}'))->toEqual(['{', '"1"', '1', '}']);
    expect(Scanner::tokenize('({})'))->toEqual(['(', '{', '}', ')']);
});

test('read of comments', function () {
    $input = <<<'TXT'
        ;; Testing 
         ;; whole line comment (not an exception)
        1 ; comment after expression
        TXT;

    expect(Scanner::tokenize($input))->toEqual(['1']);
    expect(Scanner::tokenize('1; comment after expression'))->toEqual(['1']);
});

test('read of @/deref', function () {
    expect(Scanner::tokenize('@a'))->toEqual(['@', 'a']);
});

test('read of quoting', function () {
    expect(Scanner::tokenize("'1"))->toEqual(["'", '1']);
    expect(Scanner::tokenize("'(1 2 3)"))->toEqual(["'", '(', '1', '2', '3', ')']);

    expect(Scanner::tokenize('`1'))->toEqual(['`', '1']);
    expect(Scanner::tokenize('`(1 2 3)'))->toEqual(['`', '(', '1', '2', '3', ')']);

    expect(Scanner::tokenize('~1'))->toEqual(['~', '1']);
    expect(Scanner::tokenize('~(1 2 3)'))->toEqual(['~', '(', '1', '2', '3', ')']);
    expect(Scanner::tokenize('`(1 ~2 3)'))->toEqual(['`', '(', '1', '~', '2', '3', ')']);
    expect(Scanner::tokenize('~@(1 2 3)'))->toEqual(['~@', '(', '1', '2', '3', ')']);
});

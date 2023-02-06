<?php

declare(strict_types=1);

use mal\reader\Parser;
use mal\reader\ReaderException;

test('reader errors for list', fn() => Parser::parse('(1 2'))
    ->throws(ReaderException::class, 'end of input');
test('reader errors for vector', fn() => Parser::parse('[1 2'))
    ->throws(ReaderException::class, 'end of input');
test('reader errors string 1', fn() => Parser::parse('"abc'))
    ->throws(ReaderException::class, 'unbalanced string delimiter');

test('read of quoting', function () {
    expect(Parser::parse("'1"))->toEqual('(quote 1)');
    expect(Parser::parse("'(1 2 3)"))->toEqual('(quote (1 2 3))');

    expect(Parser::parse('`1'))->toEqual('(quasiquote 1)');
    expect(Parser::parse('`(1 2 3)'))->toEqual('(quasiquote (1 2 3))');

    expect(Parser::parse('~1'))->toEqual('(unquote 1)');
    expect(Parser::parse('~(1 2 3)'))->toEqual('(unquote (1 2 3))');
    expect(Parser::parse('`(1 ~2 3)'))->toEqual('(quasiquote (1 (unquote 2) 3))');
    expect(Parser::parse('~@(1 2 3)'))->toEqual('(splice-unquote (1 2 3))');
});

test('keywords', function () {
    expect(Parser::parse(':kw'))->toEqual(':kw');
    expect(Parser::parse('(:kw1 :kw2 :kw3)'))->toEqual('(:kw1 :kw2 :kw3)');
});

test('read of vectors', function () {
    expect(Parser::parse('[]'))->toEqual('[]');
    expect(Parser::parse('[  ]'))->toEqual('[]');
    expect(Parser::parse('[+1 2]'))->toEqual('[+1 2]');
    expect(Parser::parse('[[3 4]]'))->toEqual('[[3 4]]');
    expect(Parser::parse('[+1 [+2 3]]'))->toEqual('[+1 [+2 3]]');
    expect(Parser::parse('[+1   [+2 3   ]   ]'))->toEqual('[+1 [+2 3]]');
    expect(Parser::parse('([])'))->toEqual('([])');
});

test('read of hash maps', function () {
    expect(Parser::parse('{}'))->toEqual('{}');
    expect(Parser::parse('{ }'))->toEqual('{}');
    expect(Parser::parse('{"abc" 1}'))->toEqual('{"abc" 1}');
    expect(Parser::parse('{"a" {"b" 2}}'))->toEqual('{"a" {"b" 2}}');
    expect(Parser::parse('{"a" {"b" {"c" 3}}}'))->toEqual('{"a" {"b" {"c" 3}}}');
    expect(Parser::parse('{  "a"  {"b"   {  "cde"     3   }  }}'))->toEqual('{"a" {"b" {"cde" 3}}}');
    expect(Parser::parse('{"a1" 1 "a2" 2 "a3" 3}'))->toEqual('{"a1" 1 "a2" 2 "a3" 3}');
    expect(Parser::parse('{  :a  {:b   {  :cde     3   }  }}'))->toEqual('{:a {:b {:cde 3}}}');
    expect(Parser::parse('{"1" 1}'))->toEqual('{"1" 1}');
    expect(Parser::parse('({})'))->toEqual('({})');
});

test('read of comments', function () {
    $input = <<<'TXT'
        ;; Testing 
         ;; whole line comment (not an exception)
        1 ; comment after expression
        TXT;

    expect(Parser::parse($input))->toEqual('1');
    expect(Parser::parse('1; comment after expression'))->toEqual('1');
});

test('read of @/deref', function () {
    expect(Parser::parse('@a'))->toEqual('(deref a)');
});

<?php

declare(strict_types=1);

namespace mal\reader;

use mal\types\ListType;
use mal\types\NumberType;
use mal\types\SymbolType;
use mal\types\Type;

const EOL = null;
const LPAREN = '(';
const RPAREN = ')';

final class Reader
{
    private int $current = 0;

    public function __construct(private readonly array $tokens)
    {
    }

    public function next(): null|string
    {
        return $this->tokens[$this->current++] ?? EOL;
    }

    public function peek(): null|string
    {
        return $this->tokens[$this->current] ?? EOL;
    }
}

/**
 * @param  string  $input
 * @return string[]
 */
function tokenize(string $input): array
{
    static $re = '/[\s,]*(~@|[\[\]{}()\'`~^@]|"(?:\\.|[^\\"])*"?|;.*|[^\s\[\]{}(\'"`,;)]*)/';
    preg_match_all($re, $input, $matches);

    return array_filter($matches[1]);
}

function read_str(string $input): Type
{
    $tokens = tokenize($input);
    $reader = new Reader($tokens);
    return read_form($reader);
}

function read_form(Reader $reader): Type
{
    $token = $reader->peek();

    if ($token === LPAREN) {
        return read_list($reader);
    }

    return read_atom($reader);
}

function read_atom(Reader $reader): NumberType|SymbolType
{
    $token = $reader->next();

    return is_numeric($token) ? new NumberType((float)$token) : new SymbolType($token);
}

function read_list(Reader $reader): ListType
{
    $token = $reader->next();

    if ($token !== LPAREN) {
        throw new \RuntimeException(sprintf('expected %s, got %s', LPAREN, $token));
    }

    $list = new ListType();

    while (($token = $reader->peek()) !== RPAREN) {
        if ($token === EOL) {
            throw new \RuntimeException('end of input');
        }

        $list->add_item(read_form($reader));
    }

    $reader->next();

    return $list;
}

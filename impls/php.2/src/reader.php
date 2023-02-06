<?php

declare(strict_types=1);

namespace mal\reader;

use mal\types\DerefType;
use mal\types\HashMapType;
use mal\types\ListType;
use mal\types\NumberType;
use mal\types\QuasiQuoteType;
use mal\types\QuoteType;
use mal\types\SpliceUnquoteType;
use mal\types\SymbolType;
use mal\types\Type;
use mal\types\UnquoteType;
use mal\types\VectorType;

const EOF = "\0";
const NL = "\n";

const T_ASTERISK = '*';
const T_AT = '@';
const T_BACKSLASH = '\\';
const T_BACKTICK = '`';
const T_COMMA = ',';
const T_DASH = '-';
const T_DOUBLE_QUOTE = '"';
const T_EQ = '=';
const T_GT = '>';
const T_GTE = '>=';
const T_LBRACE = '{';
const T_LBRACKET = '[';
const T_LPAREN = '(';
const T_LT = '<';
const T_LTE = '<=';
const T_POWER = '**';
const T_PLUS = '+';
const T_RBRACE = '}';
const T_RBRACKET = ']';
const T_RPAREN = ')';
const T_SEMICOLON = ';';
const T_SINGLE_QUOTE = "'";
const T_TILDE = '~';
const T_TILDE_AT = '~@';

final class Parser
{
    private int $current = 0;

    private function __construct(private readonly array $tokens)
    {
    }

    public static function parse(string $input): Type
    {
        $tokens = Scanner::tokenize($input);
        $reader = new self($tokens);
        return $reader->read_form();
    }

    private function read_form(): Type
    {
        return match ($this->peek()) {
            T_LPAREN => $this->read_list(),
            T_LBRACKET => $this->read_vector(),
            T_LBRACE => $this->read_hashmap(),
            T_SINGLE_QUOTE => $this->read_quote(),
            T_BACKTICK => $this->read_quasiquote(),
            T_TILDE => $this->read_unquote(),
            T_TILDE_AT => $this->read_splice_unquote(),
            T_AT => $this->read_deref(),
            default => $this->read_atom(),
        };
    }

    private function read_atom(): NumberType|SymbolType
    {
        $token = $this->next();

        return is_numeric($token) ? NumberType::fromString($token) : new SymbolType($token);
    }

    private function read_list(): ListType
    {
        $token = $this->next();

        if ($token !== T_LPAREN) {
            throw ParserException::unexpected(T_LPAREN, $token);
        }

        $list = new ListType();

        while (($token = $this->peek()) !== T_RPAREN) {
            if ($token === EOF) {
                throw ParserException::eof();
            }

            $list->add_item($this->read_form());
        }

        $this->next();

        return $list;
    }

    private function read_vector(): VectorType
    {
        $token = $this->next();

        if ($token !== T_LBRACKET) {
            throw ParserException::unexpected(T_LBRACKET, $token);
        }

        $vector = new VectorType();

        while (($token = $this->peek()) !== T_RBRACKET) {
            if ($token === EOF) {
                throw ParserException::eof();
            }

            $vector->add_item($this->read_form());
        }

        $this->next();

        return $vector;
    }

    private function read_hashmap(): HashMapType
    {
        $token = $this->next();

        if ($token !== T_LBRACE) {
            throw ParserException::unexpected(T_LBRACE, $token);
        }

        $vector = new HashMapType();

        while ($this->peek() !== T_RBRACE) {
            $vector->add_item($this->read_atom(), $this->read_form());
        }

        $this->next();

        return $vector;
    }

    public function read_quote(): QuoteType
    {
        $this->next();
        return new QuoteType($this->read_form());
    }

    public function read_quasiquote(): QuasiQuoteType
    {
        $this->next();
        return new QuasiQuoteType($this->read_form());
    }

    public function read_unquote(): UnquoteType
    {
        $this->next();
        return new UnquoteType($this->read_form());
    }

    public function read_splice_unquote(): SpliceUnquoteType
    {
        $this->next();
        return new SpliceUnquoteType($this->read_form());
    }

    public function read_deref(): DerefType
    {
        $this->next();
        return new DerefType($this->read_form());
    }

    private function next(): null|string
    {
        return $this->tokens[$this->current++] ?? EOF;
    }

    private function peek(): null|string
    {
        return $this->tokens[$this->current] ?? EOF;
    }
}

final class Scanner
{
    private const WHITE_SPACE = [' ', NL, T_COMMA, "\t", "\r"];
    private const SYMBOL_SPECIALS = [T_DASH, T_GT];
    private const STRING_ESCAPE = [T_BACKSLASH, T_DOUBLE_QUOTE, 'n'];
    private const DOUBLE_TOKENS = [
        T_GTE,
        T_LTE,
        T_POWER,
        T_TILDE_AT,
    ];
    private const SINGLE_TOKENS = [
        T_ASTERISK,
        T_AT,
        T_BACKTICK,
        T_EQ,
        T_GT,
        T_LBRACE,
        T_LBRACKET,
        T_LPAREN,
        T_LT,
        T_RBRACE,
        T_RBRACKET,
        T_RPAREN,
        T_SINGLE_QUOTE,
        T_TILDE,
    ];
    private string $current_char;
    private string $next_char;
    private int $current_position;
    private int $start_position;

    private function __construct(private readonly string $input)
    {
        $this->current_char = $this->input[0] ?? EOF;
        $this->next_char = $this->input[1] ?? EOF;
        $this->current_position = 0;
        $this->start_position = 0;
    }

    /**
     * @return string[]
     */
    public static function tokenize(string $input): array
    {
        $scanner = new self($input);
        $tokens = [];

        while (!$scanner->is_at_end()) {
            $scanner->start_position = $scanner->current_position;
            $tokens[] = $scanner->consume_token();
        }

        return array_values(array_filter($tokens));
    }

    private function advance(): void
    {
        $this->current_position++;
        $this->current_char = $this->input[$this->current_position] ?? EOF;
        $this->next_char = $this->input[$this->current_position + 1] ?? EOF;
    }

    private function next(): string
    {
        $this->advance();
        return $this->current_char;
    }

    private function consume_token(): string
    {
        $this->skip_whitespace();

        if ($this->is_at_end()) {
            return '';
        }

        if (T_SEMICOLON === $this->current_char) {
            $this->skip_comment();
            return '';
        }

        if (self::is_digit($this->current_char) ||
            (T_DASH === $this->current_char && self::is_digit($this->next_char))
        ) {
            $token = $this->consume_number();
        } elseif (T_DOUBLE_QUOTE === $this->current_char) {
            $token = $this->consume_string();
        } elseif (in_array($double = "{$this->current_char}{$this->next_char}", self::DOUBLE_TOKENS, true)) {
            $token = $double;
            $this->advance();
        } elseif (in_array($this->current_char, self::SINGLE_TOKENS, true)) {
            $token = $this->current_char;
        } else {
            $token = $this->consume_symbol();
        }

        $this->advance();

        return $token;
    }

    private function consume_number(): string
    {
        $token = $this->current_char;

        while (self::is_digit($this->next_char)) {
            $token .= $this->next();
        }
        return $token;
    }

    private function consume_string(): string
    {
        $start_position = $this->current_position;

        while (true) {
            if ($this->is_at_end()) {
                throw ScannerException::unbalancedString(substr($this->input, $start_position, $this->current_position));
            }

            $this->advance();

            if (T_BACKSLASH === $this->current_char) {
                if (in_array($this->next_char, self::STRING_ESCAPE)) {
                    $this->advance();
                    continue;
                }

                throw ScannerException::unrecognizedEscape(substr($this->input, $this->start_position, $this->current_position));
            }

            if ('"' === $this->current_char) {
                $this->advance();
                break;
            }
        }

        return substr($this->input, $start_position, $this->current_position - $start_position);
    }

    private function consume_symbol(): string
    {
        $token = $this->current_char;

        while (self::is_symbol($this->next_char)) {
            $token .= $this->next();
        }

        return $token;
    }

    private function skip_whitespace(): void
    {
        while (self::is_whitespace($this->current_char)) {
            $this->advance();
        }
    }

    private function skip_comment(): void
    {
        while (NL !== $this->current_char && !$this->is_at_end()) {
            $this->advance();
        }

        if (NL === $this->current_char) {
            $this->advance();
        }
    }

    private function is_at_end(): bool
    {
        return $this->current_position >= strlen($this->input);
    }

    private static function is_alpha(string $char): bool
    {
        return 1 === preg_match('/[a-zA-Z]/', $char);
    }

    private static function is_alphanumeric(string $char): bool
    {
        return self::is_alpha($char) || self::is_digit($char);
    }

    private static function is_digit(string $char): bool
    {
        return is_numeric($char);
    }

    private static function is_symbol(string $char): bool
    {
        return in_array($char, self::SYMBOL_SPECIALS) || self::is_alphanumeric($char);
    }

    private static function is_whitespace(string $char): bool
    {
        return in_array($char, self::WHITE_SPACE);
    }
}

abstract class ReaderException extends \RuntimeException
{
}

final class ParserException extends ReaderException
{
    public static function eof(): self
    {
        return new self('end of input');
    }

    public static function unexpected(string $expected, string $got): self
    {
        return new self(sprintf('expected %s, got %s', $expected, $got));
    }
}

final class ScannerException extends ReaderException
{
    public static function unbalancedString(string $string): self
    {
        return new self(
            sprintf(
                'unbalanced string delimiter; for string %s',
                $string,
            ),
        );
    }

    public static function unrecognizedEscape(string $string): self
    {
        return new self(
            sprintf(
                'unrecognized escape sequence; for string %s',
                $string,
            ),
        );
    }
}

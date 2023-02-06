<?php

declare(strict_types=1);

namespace mal\types;

use const mal\reader\T_PLUS;

abstract class Type implements \Stringable
{
}

final class DerefType extends Type
{
    public function __construct(private readonly Type $value)
    {
    }

    public function __toString(): string
    {
        return "(deref {$this->value})";
    }
}

final class QuoteType extends Type
{
    public function __construct(private readonly Type $value)
    {
    }

    public function __toString(): string
    {
        return "(quote {$this->value})";
    }
}

final class QuasiQuoteType extends Type
{
    public function __construct(private readonly Type $value)
    {
    }

    public function __toString(): string
    {
        return "(quasiquote {$this->value})";
    }
}

final class UnquoteType extends Type
{
    public function __construct(private readonly Type $value)
    {
    }

    public function __toString(): string
    {
        return "(unquote {$this->value})";
    }
}

final class SpliceUnquoteType extends Type
{
    public function __construct(private readonly Type $value)
    {
    }

    public function __toString(): string
    {
        return "(splice-unquote {$this->value})";
    }
}

final class ListType extends Type
{
    private array $items = [];

    public function add_item(Type $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('(%s)', implode(' ', array_map(fn($item) => (string)$item, $this->items)));
    }
}

final class VectorType extends Type
{
    private array $items = [];

    public function add_item(Type $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('[%s]', implode(' ', array_map(fn($item) => (string)$item, $this->items)));
    }
}

final class HashMapType extends Type
{
    private array $map = [];

    public function add_item(SymbolType $key, Type $value): self
    {
        $this->map[(string)$key] = $value;
        return $this;
    }

    public function __toString(): string
    {
        $entries = array_map(static fn($value, $key) => "$key $value", $this->map, array_keys($this->map));
        return sprintf('{%s}', implode(' ', $entries));
    }
}

final class NumberType extends Type
{
    public function __construct(private readonly float $value, private readonly bool $explicit_plus = false)
    {
    }

    public function __toString(): string
    {
        return $this->explicit_plus ? "+{$this->value}" : (string)$this->value;
    }

    public static function fromString(string $from): self
    {
        return new self((float)$from, str_starts_with($from, T_PLUS));
    }
}

final class SymbolType extends Type
{
    public function __construct(private readonly string $name)
    {
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

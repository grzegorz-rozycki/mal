<?php

declare(strict_types=1);

namespace mal\types;

abstract class Type implements \Stringable
{
}

class ListType extends Type
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

class NumberType extends Type
{
    public function __construct(private readonly float $value)
    {
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}

class SymbolType extends Type
{
    public function __construct(private readonly string $name)
    {
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

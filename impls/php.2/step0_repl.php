<?php
declare(strict_types=1);

namespace mal;

function read_line(string $line): string
{
    return $line;
}

function eval_line(string $line): string
{
    return $line;
}

function print_line(string $line): string
{
    return $line;
}

while (($line = readline('user> ')) !== false) {
    $line = read_line($line);
    $line = eval_line($line);
    $line = print_line($line);

    echo "$line\n";
}

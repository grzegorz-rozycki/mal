<?php

declare(strict_types=1);

namespace mal;

const PROMPT = 'user> ';
const HISTORY_FILE = '.mal_history';

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

if (PHP_SAPI === 'cli') {
    $history_file = getenv('HOME').DIRECTORY_SEPARATOR.HISTORY_FILE;
    \readline_read_history($history_file);

    while (($line = \readline(PROMPT)) !== false) {
        $line = trim($line);

        if ($line) {
            \readline_add_history($line);
        }

        $line = read_line($line);
        $line = eval_line($line);
        $line = print_line($line);

        echo "$line\n";
    }

    \readline_write_history($history_file);
}

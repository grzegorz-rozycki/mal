<?php
/** @noinspection AutoloadingIssuesInspection */

declare(strict_types=1);

namespace mal;

use mal\reader\ReaderException;
use mal\types\Type;

require_once __DIR__.'/vendor/autoload.php';

const PROMPT = 'user> ';
const HISTORY_FILE = '.mal_history';

function read_line(string $line): Type
{
    return reader\Parser::parse($line);
}

function eval_line(Type $ast): void
{
}

function print_line(Type $ast): void
{
    echo "$ast\n";
}

function rep(string $line): void
{
    $ast = read_line($line);
    eval_line($ast);
    print_line($ast);
}

function repl(): void
{
    $history_file = getenv('HOME').DIRECTORY_SEPARATOR.HISTORY_FILE;
    \readline_read_history($history_file);

    while (($line = \readline(PROMPT)) !== false) {
        $line = trim($line);

        if ($line) {
            \readline_add_history($line);
        }

        try {
            rep($line);
        } catch (ReaderException $e) {
            echo "{$e->getMessage()}\n";
        }
    }

    \readline_write_history($history_file);
}

if (PHP_SAPI !== 'cli') {
    return;
}

if ($argc === 1) {
    repl();
} else {
    foreach (array_slice($argv, 1) as $line) {
        rep($line);
    }
}

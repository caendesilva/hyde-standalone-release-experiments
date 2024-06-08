<?php

/** @internal Build the documentation manual. */

require_once __DIR__ . '/../vendor/autoload.php';

chdir(__DIR__ . '/..');

if (! is_dir('docs/manual')) {
    mkdir('docs/manual', recursive: true);
}

task('getting|got', 'command list', function (&$commands): void {
    $commands = hyde_exec('list --format=json --no-ansi');
    $commands = json_decode($commands, true);
}, $commands);

task('building|built', 'Html manual', function () use ($commands): void {
    $names = array_map(fn (array $command): string => $command['name'], $commands['commands']);
    $names = array_filter($names, fn (string $name): bool => ! str_starts_with($name, '_'));
    $names = array_values($names);

    echo "\n\n";
    foreach ($names as $name) {
        echo " > Building entry for command '$name'\n";
    }
    echo "\n";
});

task('building|built', 'XML manual', function (): void {
    $xml = hyde_exec('list --format=xml --no-ansi');
    file_put_contents('docs/manual/manual.xml', $xml);
});

task('building|built', 'Markdown manual', function (): void {
    $md = hyde_exec('list --format=md --no-ansi');
    file_put_contents('docs/manual/manual.md', $md);
});

/** Execute a command in the Hyde CLI and return the output. */
function hyde_exec(string $command): string
{
    exec("php hyde $command", $output, $exitCode);

    if ($exitCode !== 0) {
        throw new Exception("Failed to execute command: $command");
    }

    return implode("\n", $output);
}

/** Run a task and output the time it took to complete. */
function task(string $verb, string $subject, callable $task, &$output = null): void {
    [$start, $end] = str_contains($verb, '|')
        ? explode('|', $verb)
        : [$verb, $verb];

    [$start, $end] = [ucfirst($start), ucfirst($end)];

    $timeStart = microtime(true);
    echo "$start $subject...";

    $task($output);

    $time = round((microtime(true) - $timeStart) * 1000, 2);
    echo "\r$end $subject ($time ms)\n";
}

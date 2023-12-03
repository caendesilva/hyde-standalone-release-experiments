<?php

declare(strict_types=1);

namespace App\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Creates a new Hyde project.
 */
class NewProjectCommand extends Command
{
    /** @var string */
    protected $signature = 'new {name : The name of the project}';

    /** @var string */
    protected $description = 'Create a new Hyde project.';

    public function handle(): void
    {
        $name = $this->argument('name');

        Process::command($this->getCommand($name))
            ->run(output: $this->bufferedOutput());

        $this->newLine();
        $this->info('Project created successfully. Build something awesome!');
    }

    protected function getCommand(string $name): string
    {
        return trim(sprintf('composer create-project hyde/hyde %s --prefer-dist %s', $name, $this->withAnsi() ? '--ansi' : '--no-ansi'));
    }

    protected function withAnsi(): bool
    {
        return ! $this->option('no-ansi') || $this->option('ansi');
    }

    protected function bufferedOutput(): Closure
    {
        return function (string $type, string $buffer): void {
            $this->output->write($buffer);
        };
    }
}

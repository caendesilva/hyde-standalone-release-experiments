<?php

/** @internal Build the documentation manual. */

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/lib/ansi-themes.php';

chdir(__DIR__.'/..');

if (! is_dir('docs/manual')) {
    mkdir('docs/manual', recursive: true);
}

task('getting|got', 'command list', function (&$commands): void {
    $commands = hyde_exec('list --format=json --no-ansi', true);
    $commands = json_decode($commands, true);
}, $commands);

task('building|built', 'Html manual', function () use ($commands): void {
    $names = array_map(fn (array $command): string => $command['name'], $commands['commands']);
    $names = array_filter($names, fn (string $name): bool => ! in_array($name, ['_complete', 'completion', 'standalone:build']));
    $names = array_values($names);

    $manual = [];

    echo "\n\n";
    foreach ($names as $name) {
        ansi_echo(" > Building entry for command '$name'\n", '37');

        $info = hyde_exec("help $name --ansi", true);
        $info = ansi_to_html($info);
        $manual[] = "<h1>$name</h1>\n$info";
    }
    echo "\n";

    // In the future, we could save each entry to a separate file, but now we just implode them into one.
    $entries = implode("\n\n<hr>\n\n", $manual);
    $themes = ansi_html_themes();
    $themeSelector = theme_selector_widget();
    $defaultTheme = get_theme_key(get_default_ansi_theme());
    $template = file_get_contents('.github/docs/templates/manual.blade.php');
    $manual = str_replace(['{{ $themes }}', '{{ $themeSelector }}', '{{ $theme }}', '{{ $entries }}'], [$themes, $themeSelector, $defaultTheme, $entries], $template);

    file_put_contents('docs/manual/manual.html', $manual);
});

task('building|built', 'XML manual', function (): void {
    $xml = hyde_exec('list --format=xml --no-ansi', true);
    file_put_contents('docs/manual/manual.xml', $xml);
});

task('building|built', 'Markdown manual', function (): void {
    $md = hyde_exec('list --format=md --no-ansi', true);
    file_put_contents('docs/manual/manual.md', $md);
});

/** Execute a command in the Hyde CLI and return the output. */
function hyde_exec(string $command, bool $cache = false): string
{
    $cacheKey = 'bin/cache/'.md5($command);

    if ($cache && file_exists($cacheKey)) {
        return file_get_contents($cacheKey);
    }

    exec("php hyde $command", $output, $exitCode);

    if ($exitCode !== 0) {
        throw new Exception("Failed to execute command: $command");
    }

    $output = implode("\n", $output);

    if ($cache) {
        file_put_contents($cacheKey, $output);
    }

    return $output;
}

/** Output a message with ANSI colors. */
function ansi_echo(string $message, string $color): void
{
    echo "\e[{$color}m$message\e[0m";
}

/** Run a task and output the time it took to complete. */
function task(string $verb, string $subject, callable $task, &$output = null): void
{
    [$start, $end] = str_contains($verb, '|')
        ? explode('|', $verb)
        : [$verb, $verb];

    [$start, $end] = [ucfirst($start), ucfirst($end)];

    $timeStart = microtime(true);
    ansi_echo("$start $subject...", '36');

    $task($output);

    $time = round((microtime(true) - $timeStart) * 1000, 2);
    ansi_echo("\r$end $subject ($time ms)\n", '32');
}

function ansi_to_html(string $output): string
{
    $output = htmlspecialchars($output);
    $output = preg_replace('/\e\[(\d+)(;\d+)*m/', '</span><span class="ansi-$1">', $output);
    $output = "<span class=\"ansi-0\">$output</span>";

    return <<<HTML
    <pre class="terminal-screen">$output</pre>
    HTML;
}

function ansi_html_themes(): string
{
    return implode("\n", array_map('build_theme', get_themes()));
}

function theme_selector_widget(): string
{
    $themes = get_themes();

    $options = '';
    foreach ($themes as $theme) {
        $key = get_theme_key($theme);
        $name = ucfirst($key);

        if ($theme::class === get_default_ansi_theme()::class) {
            $options .= "<option value=\"$name\" selected>$name</option>";
        } else {
            $options .= "<option value=\"$key\">$name</option>";
        }
    }

    return <<<HTML
    <label for="theme-selector">Theme:</label>
    <select id="theme-selector">
        $options
    </select>

    <script>
        const selector = document.getElementById('theme-selector');
        selector.addEventListener('change', () => {
            const theme = selector.value;
            document.body.className = '';
            document.body.classList.add('theme-' + theme.toLowerCase().replace('theme', ''));
        });
    </script>
    HTML;
}

function build_theme(ThemeInterface $theme): string
{
    $colors = $theme::colors();

    $identifier = strtolower(str_replace('Theme', '', (new ReflectionClass($theme))->getShortName()));

    $theme = "\n".<<<CSS
            .theme-$identifier .terminal-screen {
                 color: {$theme::textColor()};
                 background: {$theme::background()};
                 font-family: {$theme::fontFamily()};
                 font-size: 12px;
                 width: 128ch;
                 overflow-x: auto;
                 padding: 1em;
            }
    CSS;

    $theme .= "\n";
    foreach ($colors as $code => $color) {
        $theme .= "        .theme-$identifier .ansi-$code { color: $color; }\n";
    }

    return rtrim($theme)."\n    ";
}

function get_theme_key(ThemeInterface $theme): string
{
    return strtolower(str_replace('Theme', '', (new ReflectionClass($theme))->getShortName()));
}

function get_themes(): array
{
    return [
        new FiraTheme(),
        new ClassicTheme(),
        new CampbellTheme(),
    ];
}

function get_default_ansi_theme(): ThemeInterface
{
    return new FiraTheme();
}

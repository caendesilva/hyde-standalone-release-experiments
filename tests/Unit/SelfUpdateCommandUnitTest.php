<?php

use App\Commands\SelfUpdateCommand;

$versions = [
    ['1.2.3', ['major' => 1, 'minor' => 2, 'patch' => 3]],
    ['2.5.0', ['major' => 2, 'minor' => 5, 'patch' => 0]],
    ['0.0.1', ['major' => 0, 'minor' => 0, 'patch' => 1]],
];

it('parses the version correctly', function ($input, $expectedOutput) {
    expect((new InspectableSelfUpdateCommand())->parseVersion($input))->toBe($expectedOutput);
})->with($versions);

it('returns an array with integer values', function ($input, $expectedOutput) {
    $result = (new InspectableSelfUpdateCommand())->parseVersion($input);

    expect($result)->toEqual($expectedOutput)
        ->and($result['major'])->toBeInt()
        ->and($result['minor'])->toBeInt()
        ->and($result['patch'])->toBeInt();
})->with($versions);

it('correctly compares versions', function ($currentVersion, $latestVersion, $expectedResult) {
    $class = new InspectableSelfUpdateCommand();

    $result = $class->compareVersions($class->parseVersion($currentVersion), $class->parseVersion($latestVersion));

    expect($result)->toBe($class->constants($expectedResult));
})->with([
    ['1.2.3', '1.2.3', 'STATE_UP_TO_DATE'],
    ['1.2.3', '2.0.0', 'STATE_BEHIND'],
    ['2.0.0', '1.2.3', 'STATE_AHEAD'],
]);

it('validates release data correctly', function () {
    $data = ['tag_name' => 'v1.0.0', 'assets' => [['name' => 'hyde', 'browser_download_url' => 'https://example.com']]];

    (new InspectableSelfUpdateCommand())->validateReleaseData($data);

    // No exception thrown means validation passed
    expect(true)->toBeTrue();
});

it('throws exception if release data is invalid', function ($data) {
    $this->expectException(AssertionError::class);

    (new InspectableSelfUpdateCommand())->validateReleaseData($data);
})->with([
    [[]], // Empty data
    [['tag_name' => 'v1.0.0']], // Missing assets key
    [['assets' => []]], // Empty assets array
    [['assets' => [['name' => 'invalid_name']]]], // Invalid asset name
]);

it('returns the correct application path', function () {
    $class = new InspectableSelfUpdateCommand();
    $result = $class->findApplicationPath();

    // Assertions for the application path
    expect($result)->toBeString()
        ->and($result)->not->toBeEmpty()
        ->and(file_exists($result))->toBeTrue();
});

test('get debug environment', function () {
    $class = new InspectableSelfUpdateCommand();
    $result = $class->getDebugEnvironment();

    expect($result)->toBeString()
        ->and($result)->not->toBeEmpty()
        ->and($result)->toContain('Application version: v')
        ->and($result)->toContain('PHP version:         v')
        ->and($result)->toContain('Operating system:    ');
});

it('strips personal information from markdown', function () {
    $class = new InspectableSelfUpdateCommand();
    $markdown = "Error occurred in /home/user/project/file.php\nStack trace:\n/home/user/project/file.php:10";

    $result = $class->stripPersonalInformation($markdown);

    // Assertions
    expect($result)->not->toContainString(getenv('USER') ?: getenv('USERNAME'));
    expect($result)->not->toContainString(base_path().DIRECTORY_SEPARATOR);
    expect($result)->toContain('<USERNAME>');
    expect($result)->toContain('<project>');
});

it('does not modify markdown without personal information', function () {
    $class = new InspectableSelfUpdateCommand();
    $markdown = 'No personal information present.';

    $result = $class->stripPersonalInformation($markdown);

    // Assertions
    expect($result)->toBe($markdown);
});

class InspectableSelfUpdateCommand extends SelfUpdateCommand
{
    public function property(string $property): mixed
    {
        return $this->$property;
    }

    public function __call($method, $parameters)
    {
        return $this->$method(...$parameters);
    }

    public function constants(string $constant): mixed
    {
        return constant("self::$constant");
    }
}

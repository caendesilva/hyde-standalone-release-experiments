<?php /** @noinspection PhpPossiblePolymorphicInvocationInspection */

use Hyde\Foundation\HydeKernel;
use Hyde\Foundation\Application;
use Hyde\Foundation\ConsoleKernel;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;

putenv('HYDE_WORKING_DIR=/path/to/working/dir');
putenv('HYDE_TEMP_DIR=/path/to/temp/dir');

beforeEach(function () {
    $this->app = require __DIR__ . '/../../app/anonymous-bootstrap.php';
});

test('anonymous bootstrapper returns application', function () {
    expect($this->app)->toBeInstanceOf(Application::class);
});

it('has correct base path', function () {
    expect($this->app->basePath())->toBe('/path/to/working/dir');
});

it('has correct config path', function () {
    expect($this->app->configPath())->toBe('/path/to/working/dir'.DIRECTORY_SEPARATOR.'config');
});

it('binds console kernel', function () {
    expect($this->app->make(Kernel::class))->toBeInstanceOf(ConsoleKernel::class);
});

it('binds exception handler', function () {
    expect($this->app->make(ExceptionHandler::class))->toBeInstanceOf(Handler::class);
});

it('binds Hyde kernel', function () {
    expect($this->app->make(HydeKernel::class))->toBeInstanceOf(HydeKernel::class);
});

it('binds Hyde kernel as singleton', function () {
    expect($this->app->make(HydeKernel::class))->toBe($this->app->make(HydeKernel::class));
});

it('sets Hyde kernel instance', function () {
    expect(HydeKernel::getInstance())->toBe($this->app->make(HydeKernel::class));
});

it('sets Hyde kernel path', function () {
    expect(HydeKernel::getInstance()->path())->toBe('/path/to/working/dir');
});

it('sets the cached packages path', function () {
    expect($this->app->getCachedPackagesPath())->toBe('/path/to/temp/dir/app/storage/framework/cache/packages.php');
});

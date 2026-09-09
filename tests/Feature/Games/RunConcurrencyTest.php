<?php

use App\Games\FourLetterWords\RunStore;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

it('serializes conflicting updates to one account run', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Concurrent writes require the disposable PostgreSQL test database.');
    }
    $this->artisan('migrate:fresh');
    $id = '00000000-0000-4000-8000-000000000021';
    app(RunStore::class)->save('acc_fixture', $id, ['CARE']);
    $marker = tempnam(sys_get_temp_dir(), 'flw-lock-');
    unlink($marker);
    $code = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'pgsql', 'database.connections.pgsql' => json_decode(getenv('FLW_TEST_DATABASE'), true)]);
try {
    Illuminate\Support\Facades\DB::transaction(function () {
        app(App\Games\FourLetterWords\RunStore::class)->save('acc_fixture', getenv('FLW_TEST_RUN'), ['CARE', getenv('FLW_TEST_WORD')]);
        if (getenv('FLW_TEST_MARKER')) {
            file_put_contents(getenv('FLW_TEST_MARKER'), 'locked');
            usleep(800000);
        }
    });
    echo 'saved';
} catch (App\Games\FourLetterWords\RunConflict) {
    echo 'conflict';
}
PHP;
    $env = ['APP_ENV' => 'testing', 'FLW_TEST_DATABASE' => json_encode(config('database.connections.pgsql')), 'FLW_TEST_RUN' => $id];
    $first = new Process([PHP_BINARY, '-r', $code], base_path(), [...$env, 'FLW_TEST_WORD' => 'CARD', 'FLW_TEST_MARKER' => $marker], timeout: 10);
    $second = new Process([PHP_BINARY, '-r', $code], base_path(), [...$env, 'FLW_TEST_WORD' => 'CORE', 'FLW_TEST_MARKER' => ''], timeout: 10);
    try {
        $first->start();
        $deadline = microtime(true) + 5;
        while (! file_exists($marker) && $first->isRunning() && microtime(true) < $deadline) {
            usleep(10000);
        }
        expect(file_exists($marker))->toBeTrue($first->getErrorOutput());
        $second->start();
        $first->wait();
        $second->wait();
        expect($first->isSuccessful())->toBeTrue($first->getErrorOutput());
        expect($second->isSuccessful())->toBeTrue($second->getErrorOutput());
        expect($first->getOutput())->toBe('saved');
        expect($second->getOutput())->toBe('conflict');
        expect(app(RunStore::class)->find('acc_fixture', $id)['submissions'])->toBe(['CARE', 'CARD']);
    } finally {
        $first->stop();
        $second->stop();
        @unlink($marker);
        RefreshDatabaseState::$migrated = false;
    }
});

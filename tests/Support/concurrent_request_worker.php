<?php

/*
 * Runs a single HTTP request as a given user in its own PHP process against a shared SQLite
 * file, so tests can fire several requests at the database at the same moment.
 *
 * Usage: php concurrent_request_worker.php <db-path> <user-id> <method> <path> <json-params> <start-at> [pause-after-sql]
 */

use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

[, $databasePath, $userId, $method, $path, $params, $startAt] = $argv;
$pauseAfter = $argv[7] ?? 'count(*)';

foreach ([
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => $databasePath,
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $_SERVER[$key] = $value;
}

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$request = Request::create($path, $method, json_decode($params, true));
$app->instance('request', $request);

Auth::guard('web')->setUser(User::findOrFail($userId));

// Pause after every query containing the given SQL fragment (by default, every count), widening
// the window in which a competing request could act on the same data if it were not locked.
DB::listen(function ($query) use ($pauseAfter) {
    if ($pauseAfter !== '' && str_contains($query->sql, $pauseAfter)) {
        usleep(300_000);
    }
});

// Wait for the agreed start time so all workers hit the database together.
while (microtime(true) < (float) $startAt) {
    usleep(1_000);
}

$response = $kernel->handle($request);
$session = $app['session.store'];

echo json_encode([
    'status' => $response->getStatusCode(),
    'success' => $session->get('success'),
    'error' => $session->get('error'),
]);

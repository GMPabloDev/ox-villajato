<?php

use Illuminate\Contracts\Console\Kernel;
use Tests\DuskIsolation;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/_dusk_isolation_probe') {
    $token = getenv('DUSK_ISOLATED_TOKEN');
    if (! is_string($token) || ! hash_equals($token, $_GET['token'] ?? '')) {
        http_response_code(404);
        exit;
    }

    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    echo $token.':'.DuskIsolation::verifyConnection($app);
    exit;
}

$public = dirname(__DIR__).'/public';
if ($uri !== '/' && is_file($public.$uri)) {
    return false;
}

require $public.'/index.php';

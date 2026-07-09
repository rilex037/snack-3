<?php

declare(strict_types=1);

use Snack\Env;

$app = require dirname(__DIR__) . '/snack/bootstrap.php';

try {
    $app->run();
} catch (\Throwable $exception) {
    http_response_code(500);

    if (Env::get('APP_DEBUG', 'false') === 'true') {
        header('Content-Type: text/plain; charset=UTF-8');
        echo $exception::class . ': ' . $exception->getMessage() . "\n\n" . $exception->getTraceAsString();
    } else {
        echo '500 Internal Server Error';
    }
}

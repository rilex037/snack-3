<?php

declare(strict_types=1);

use Snack\Application;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config.php';

$app = new Application();
$app->boot($config);

return $app;

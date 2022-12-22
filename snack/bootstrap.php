<?php

use Snack\Snack;

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

$CONFIG = require_once dirname(dirname(__FILE__)) . '/config.php';

Snack::getInstance()
    ->setOrm(new $CONFIG['instances']['orm'])
    ->setRouter(new $CONFIG['instances']['router']);

$router = Snack::getInstance()->getRouter();

foreach ($CONFIG['routes'] as $routeFile) {
    require_once $routeFile;
}

$router->run();

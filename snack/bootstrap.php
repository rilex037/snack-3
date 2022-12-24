<?php

declare(strict_types=1);

use League\Plates\Engine;
use Snack\Snack;

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

$CONFIG = require_once dirname(dirname(__FILE__)) . '/config.php';

Snack::getInstance()
    ->setOrm(new $CONFIG['instances']['orm'])
    ->setRouter(new $CONFIG['instances']['router'])
    ->setTemplates(new Engine($CONFIG['templatesPath']));

$router = Snack::getInstance()->getRouter();

foreach ($CONFIG['routes'] as $routeFile) {
    require_once $routeFile;
}

$router->run();

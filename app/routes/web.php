<?php

declare(strict_types=1);

use App\Http\Controller\HomeController;

$router->get('/', function () {
    echo (new HomeController())->index();
});

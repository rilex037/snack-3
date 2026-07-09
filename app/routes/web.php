<?php

declare(strict_types=1);

use App\Http\Controller\HomeController;
use Snack\Routing\RouterInterface;

$router->get('/', [HomeController::class, 'index']);

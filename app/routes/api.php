<?php

declare(strict_types=1);

use App\Http\Controller\TaskController;
use Snack\Routing\RouterInterface;

$router->get('/api/tasks', [TaskController::class, 'index']);
$router->get('/api/tasks/stats', [TaskController::class, 'stats']);
$router->post('/api/tasks', [TaskController::class, 'store']);
$router->put('/api/tasks/(\d+)', [TaskController::class, 'update']);
$router->delete('/api/tasks/(\d+)', [TaskController::class, 'destroy']);

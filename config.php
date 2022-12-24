<?php

declare(strict_types=1);

return [

    'instances' => [
        'orm' => Snack\Orm\Pdo\PdoOrm::class,
        'router' => Bramus\Router\Router::class,
    ],

    'routes' => ['web' => '../app/routes/web.php'],

    'templatesPath' => '../app/templates'
];

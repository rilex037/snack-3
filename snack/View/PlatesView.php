<?php

declare(strict_types=1);

namespace Snack\View;

use League\Plates\Engine;

final class PlatesView implements ViewInterface
{
    public function __construct(private readonly Engine $engine)
    {
    }

    public function render(string $template, array $data = []): string
    {
        return $this->engine->render($template, $data);
    }

    public function engine(): Engine
    {
        return $this->engine;
    }
}

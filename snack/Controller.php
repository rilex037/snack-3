<?php

declare(strict_types=1);

namespace Snack;

use Snack\Http\Response;
use Snack\View\ViewInterface;

abstract class Controller
{
    public function __construct(protected readonly ViewInterface $view)
    {
    }

    protected function view(string $template, array $data = []): Response
    {
        return Response::html($this->view->render($template, $data));
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $to, int $status = 302): Response
    {
        return Response::redirect($to, $status);
    }
}

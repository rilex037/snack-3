<?php

declare(strict_types=1);

namespace  App\Http\Controller;

use Snack\Controller;

final class HomeController extends Controller
{
    public function index(): string
    {
        return $this->template
            ->render('sections/body', ['title' => 'Test Title']);
    }
}

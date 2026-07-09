<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Models\Task;
use Snack\Controller;
use Snack\Http\Response;

final class HomeController extends Controller
{
    public function index(): Response
    {
        $tasks = Task::query()->orderBy('created_at', 'desc')->get();

        return $this->view('sections/body', [
            'title' => 'Snack — Task Board',
            'tasks' => array_map(static fn (Task $task): array => $task->toArray(), $tasks),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Models\Task;
use Snack\Controller;
use Snack\Database\ConnectionInterface;
use Snack\Exception\ModelNotFoundException;
use Snack\Http\Request;
use Snack\Http\Response;
use Snack\View\ViewInterface;

final class TaskController extends Controller
{
    public function __construct(
        ViewInterface $view,
        private readonly ConnectionInterface $connection,
    ) {
        parent::__construct($view);
    }

    public function index(): Response
    {
        $tasks = Task::query()->orderBy('created_at', 'desc')->get();

        return $this->json(array_map(static fn (Task $task): array => $task->toArray(), $tasks));
    }

    public function store(Request $request): Response
    {
        $title = trim((string) $request->input('title', ''));

        if ($title === '') {
            return $this->json(['message' => 'Title is required.'], 422);
        }

        $task = Task::create(['title' => $title, 'done' => false]);

        return $this->json($task->toArray(), 201);
    }

    public function update(int $id, Request $request): Response
    {
        try {
            $task = Task::findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], 404);
        }

        if ($request->input('title') !== null) {
            $task->title = trim((string) $request->input('title'));
        }

        if ($request->input('done') !== null) {
            $task->done = (bool) $request->input('done');
        }

        $task->save();

        return $this->json($task->toArray());
    }

    public function destroy(int $id): Response
    {
        Task::query()->where('id', '=', $id)->delete();

        return Response::noContent();
    }

    public function stats(): Response
    {
        $row = $this->connection->selectOne(
            'SELECT COUNT(*) AS total, SUM(CASE WHEN done THEN 1 ELSE 0 END) AS completed FROM tasks'
        );

        return $this->json([
            'total' => (int) ($row['total'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
        ]);
    }
}

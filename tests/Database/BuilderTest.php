<?php

declare(strict_types=1);

namespace Test\Database;

use PDO;
use PHPUnit\Framework\TestCase;
use Snack\Database\Connection;
use Snack\Database\Query\Builder;
use Snack\Exception\QueryException;

final class BuilderTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, published INTEGER, views INTEGER)');

        $this->connection = new Connection($pdo, 'sqlite');
    }

    private function builder(): Builder
    {
        return Builder::on($this->connection, 'posts');
    }

    public function testInsertGetIdAndFind(): void
    {
        $id = $this->builder()->insertGetId(['title' => 'Hello', 'published' => 1, 'views' => 10]);

        $row = $this->builder()->find((int) $id);

        $this->assertSame('Hello', $row['title']);
    }

    public function testMultiRowInsert(): void
    {
        $this->builder()->insert([
            ['title' => 'A', 'published' => 1, 'views' => 1],
            ['title' => 'B', 'published' => 0, 'views' => 2],
        ]);

        $this->assertSame(2, $this->builder()->count());
    }

    public function testWhereHelperWithImplicitEquals(): void
    {
        $this->builder()->insert(['title' => 'A', 'published' => 1, 'views' => 1]);
        $this->builder()->insert(['title' => 'B', 'published' => 0, 'views' => 2]);

        $rows = $this->builder()->where('published', 1)->get();

        $this->assertCount(1, $rows);
        $this->assertSame('A', $rows[0]['title']);
    }

    public function testOrWhereAndNestedWhere(): void
    {
        $this->builder()->insert(['title' => 'A', 'published' => 1, 'views' => 100]);
        $this->builder()->insert(['title' => 'B', 'published' => 0, 'views' => 5]);
        $this->builder()->insert(['title' => 'C', 'published' => 0, 'views' => 1]);

        $rows = $this->builder()
            ->where('published', '=', 1)
            ->orWhere(function (Builder $query): void {
                $query->where('views', '>', 3)->where('published', '=', 0);
            })
            ->orderBy('title')
            ->get();

        $this->assertSame(['A', 'B'], array_column($rows, 'title'));
    }

    public function testWhereInAndWhereNotIn(): void
    {
        $this->builder()->insert(['title' => 'A', 'published' => 1, 'views' => 1]);
        $this->builder()->insert(['title' => 'B', 'published' => 1, 'views' => 2]);
        $this->builder()->insert(['title' => 'C', 'published' => 1, 'views' => 3]);

        $in = $this->builder()->whereIn('views', [1, 3])->orderBy('views')->pluck('title');
        $notIn = $this->builder()->whereNotIn('views', [1, 3])->pluck('title');

        $this->assertSame(['A', 'C'], $in);
        $this->assertSame(['B'], $notIn);
    }

    public function testWhereRawEscapeHatch(): void
    {
        $this->builder()->insert(['title' => 'A', 'published' => 1, 'views' => 42]);

        $row = $this->builder()->whereRaw('views = ?', [42])->first();

        $this->assertSame('A', $row['title']);
    }

    public function testUpdateOnlyAffectsMatchingRows(): void
    {
        $this->builder()->insert(['title' => 'A', 'published' => 0, 'views' => 1]);
        $this->builder()->insert(['title' => 'B', 'published' => 0, 'views' => 1]);

        $affected = $this->builder()->where('title', 'A')->update(['published' => 1]);

        $this->assertSame(1, $affected);
        $this->assertSame(1, $this->builder()->where('published', 1)->count());
    }

    public function testDeleteRequiresAWhereClause(): void
    {
        $this->expectException(QueryException::class);

        $this->builder()->delete();
    }

    public function testTruncateRemovesEveryRow(): void
    {
        $this->builder()->insert(['title' => 'A', 'published' => 0, 'views' => 1]);
        $this->builder()->truncate();

        $this->assertSame(0, $this->builder()->count());
    }

    public function testInvalidOperatorIsRejected(): void
    {
        $this->expectException(QueryException::class);

        $this->builder()->where('title', 'DROP TABLE posts; --', 'x')->get();
    }

    public function testExistsAndDoesntExist(): void
    {
        $this->builder()->insert(['title' => 'A', 'published' => 1, 'views' => 1]);

        $this->assertTrue($this->builder()->where('title', 'A')->exists());
        $this->assertTrue($this->builder()->where('title', 'Z')->doesntExist());
    }

    public function testChunkIteratesEveryPage(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->builder()->insert(['title' => "T{$i}", 'published' => 1, 'views' => $i]);
        }

        $seen = [];

        $this->builder()->orderBy('views')->chunk(2, function (array $rows) use (&$seen): void {
            foreach ($rows as $row) {
                $seen[] = $row['title'];
            }
        });

        $this->assertSame(['T0', 'T1', 'T2', 'T3', 'T4'], $seen);
    }
}

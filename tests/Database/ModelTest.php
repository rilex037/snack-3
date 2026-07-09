<?php

declare(strict_types=1);

namespace Test\Database;

use PDO;
use PHPUnit\Framework\TestCase;
use Snack\Database\Connection;
use Snack\Database\Model;
use Snack\Exception\ModelNotFoundException;

final class Article extends Model
{
    protected string $table = 'articles';
    protected array $fillable = ['title', 'published'];
    protected array $casts = ['published' => 'bool'];
}

final class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT,
                published INTEGER,
                created_at TEXT,
                updated_at TEXT
            )'
        );

        Article::setConnection(new Connection($pdo, 'sqlite'));
    }

    public function testCreateAndFind(): void
    {
        $article = Article::create(['title' => 'Hello world', 'published' => true]);

        $this->assertTrue($article->exists());
        $this->assertIsInt($article->id);

        $found = Article::find($article->id);

        $this->assertNotNull($found);
        $this->assertSame('Hello world', $found->title);
        $this->assertTrue($found->published);
    }

    public function testFindOrFailThrowsWhenMissing(): void
    {
        $this->expectException(ModelNotFoundException::class);

        Article::findOrFail(999);
    }

    public function testFillRespectsFillable(): void
    {
        $article = new Article(['title' => 'A', 'published' => true, 'id' => 999]);

        $this->assertNull($article->id);
        $this->assertSame('A', $article->title);
    }

    public function testSaveUpdatesOnlyWhenExisting(): void
    {
        $article = Article::create(['title' => 'Original', 'published' => false]);

        $article->title = 'Updated';
        $article->save();

        $fromDb = Article::find($article->id);

        $this->assertSame('Updated', $fromDb->title);
        $this->assertSame(1, Article::count());
    }

    public function testDeleteRemovesTheRow(): void
    {
        $article = Article::create(['title' => 'Bye', 'published' => false]);
        $article->delete();

        $this->assertNull(Article::find($article->id));
        $this->assertFalse($article->exists());
    }

    public function testStaticCallsForwardToTheQueryBuilder(): void
    {
        Article::create(['title' => 'A', 'published' => true]);
        Article::create(['title' => 'B', 'published' => false]);

        $published = Article::where('published', '=', true)->get();

        $this->assertCount(1, $published);
        $this->assertSame('A', $published[0]->title);
    }

    public function testToArrayAppliesCasts(): void
    {
        $article = Article::create(['title' => 'A', 'published' => true]);

        $this->assertSame(true, $article->toArray()['published']);
    }
}

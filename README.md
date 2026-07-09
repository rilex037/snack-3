# Snack

Slim MVC micro-framework for PHP 8.1+. Three dependencies, a real query
builder/ORM, constructor-injected everything, server-rendered views
enhanced by Vue 3 islands — no npm, no bundler, no build step for the
frontend.

## Requirements

- PHP 8.1+
- `pdo_mysql` or `pdo_sqlite`
- Composer

## Install

```bash
composer install
cp .env.example .env
```

Edit `.env`:

```
APP_DEBUG=true
DB_DRIVER=mysql        # or sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=snack
DB_USER=root
DB_PASSWORD=
```

For zero-setup local dev with no MySQL, use sqlite instead:

```
DB_DRIVER=sqlite
DB_NAME=/absolute/path/to/database/snack.sqlite
```

Run migrations, then start the dev server:

```bash
php bin/migrate.php
php -S 127.0.0.1:8000 -t public
```

Or with Docker: `make dockerize`, then `make migrate`.

## Testing

```bash
composer test      # phpunit — Container, Query Builder, Model, all against in-memory sqlite
composer phpstan    # static analysis, level 6
```

## Deploying to shared hosting

Most shared hosts give you one web root and no way to point it at a
`public/` subfolder, no CLI/SSH access, and no `composer` on the
server. `composer build` handles this:

```bash
composer install
composer build
```

This produces a self-contained `build/` folder (gitignored) with
`index.php` moved to the root, `public/assets/` flattened to
`build/assets/`, `vendor/` copied as-is, and an `.htaccess` that routes
everything through the front controller while blocking direct access
to `snack/`, `app/`, `vendor/`, `database/`, `config.php`, and `.env`.

Upload the *contents* of `build/` to the host's web root via FTP.
Since there's no CLI on the host, run migrations by hand — either
execute the relevant `.sql` file in `database/migrations/{mysql,sqlite}/`
through a DB admin panel, or drop a one-off PHP script that runs it and
delete it after.

If you hit `open_basedir restriction in effect`, the absolute path in
`DB_NAME` doesn't match the host's actual allowed path — check your
hosting control panel for the exact allowed base directory.

## Dependency injection

`Application extends Container`. Bindings (`ConnectionInterface`,
`ViewInterface`, `RouterInterface`, ...) are registered in
`Application::boot()`. Controllers are resolved through the container:

```php
final class TaskController extends Controller
{
    public function __construct(
        ViewInterface $view,
        private readonly ConnectionInterface $connection,
    ) {
        parent::__construct($view);
    }
}
```

Route handlers can also type-hint `Request`; URL capture groups
(`/api/tasks/(\d+)`) map to remaining scalar-typed parameters by
position.

## Query builder

```php
use Snack\Database\Query\Builder;

Builder::on($connection, 'posts')
    ->select('id', 'title')
    ->where('published', '=', true)
    ->orWhere(fn (Builder $q) => $q->where('views', '>', 1000)->where('featured', '=', true))
    ->whereIn('author_id', [1, 2, 3])
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

// Raw, mixed into the same chain
Builder::on($connection, 'posts')
    ->selectRaw('COUNT(*) as total, author_id')
    ->whereRaw('created_at > ?', [$since])
    ->groupBy('author_id')
    ->havingRaw('COUNT(*) > ?', [5])
    ->get();

// Fully raw, no builder
$connection->select('SELECT * FROM posts WHERE id = ?', [$id]);
```

Supported: `select/selectRaw/addSelect/distinct`,
`where/orWhere/whereRaw/orWhereRaw/whereIn/whereNotIn/whereNull/whereNotNull/whereBetween`
(including nested `where(fn (Builder $q) => ...)` groups),
`join/leftJoin/rightJoin`, `groupBy/having/havingRaw`,
`orderBy/orderByRaw`, `limit/offset/forPage`, terminal reads
`get/first/find/value/pluck/count/exists/doesntExist/chunk`, writes
`insert`/`insertGetId`/`update`/`delete` (refuses to run without a
`where()` — use `truncate()` if that's intended),
`increment`/`decrement`. `where()` operators are checked against an
allow-list.

## Models

```php
final class Task extends Model
{
    protected string $table = 'tasks';    // optional, inferred otherwise
    protected array $fillable = ['title', 'done'];
    protected array $casts = ['done' => 'bool'];
}

Task::create(['title' => 'Ship it']);
Task::where('done', '=', false)->orderBy('created_at', 'desc')->get();
$task = Task::findOrFail(1);
$task->done = true;
$task->save();   // only writes changed columns
```

`Model` calls are forwarded to `ModelQueryBuilder`, which wraps a
`Builder` and hydrates rows into model instances — full builder API,
no duplicated logic.

## Frontend

`app/templates/sections/body.php` renders real, final HTML — usable
with JS disabled. It's wrapped in a `data-vue-component` /
`data-vue-props` pair:

```php
<div <?=$this->vueIslandAttrs('TaskManager', ['initialTasks' => $tasks])?>>
    ...server-rendered markup...
</div>
```

`public/assets/js/app.js` loads as a native ES module. An import map
resolves `import { createApp } from 'vue'` to a CDN build — no npm, no
bundler. It scans for `[data-vue-component]` islands and mounts each
with `createApp(...).mount(el)`.

This is plain mount-and-replace, not SSR hydration
(`createSSRApp`) — true hydration requires the existing DOM to match
Vue's render output node-for-node, which hand-written PHP markup can't
guarantee. Since SEO/first paint only depend on PHP's initial output,
mount-and-replace gets the same practical result.

Component files: an import, props, and a `setup()` delegating to a
composable, template as an inline JS string. State/API calls live in
`composables/` and `services/` — same split as a `<script setup>` SFC
project, without the SFC compiler.

## Adding a route

```php
// app/routes/web.php
$router->get('/tasks/(\d+)', [TaskShowController::class, 'index']);
```

## Adding a model

```php
// app/Models/Comment.php
final class Comment extends Model
{
    protected array $fillable = ['task_id', 'body'];
}
```

## Adding a migration

Drop `NNNN_description.sql` into `database/migrations/{mysql,sqlite}/`
and run `php bin/migrate.php` — applied migrations are tracked in a
`migrations` table.

## License

MIT

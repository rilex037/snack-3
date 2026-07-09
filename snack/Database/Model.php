<?php

declare(strict_types=1);

namespace Snack\Database;

use Snack\Database\Query\Builder;
use Snack\Exception\SnackException;

abstract class Model implements \JsonSerializable
{
    private static ?ConnectionInterface $resolvedConnection = null;

    protected string $table = '';
    protected string $primaryKey = 'id';
    protected bool $incrementing = true;
    protected bool $timestamps = true;

    protected array $fillable = [];

    protected array $casts = [];

    private array $attributes = [];

    private array $original = [];

    private bool $exists = false;

    final public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public static function setConnection(ConnectionInterface $connection): void
    {
        self::$resolvedConnection = $connection;
    }

    public static function getConnection(): ConnectionInterface
    {
        return self::$resolvedConnection
            ?? throw new SnackException('No database connection set. Call Model::setConnection() during bootstrap.');
    }

    public static function query(): ModelQueryBuilder
    {
        $builder = Builder::on(static::getConnection(), (new static())->getTable());

        return new ModelQueryBuilder($builder, static::class);
    }

    public static function find(int|string $id): ?static
    {
        return static::query()->find($id);
    }

    public static function findOrFail(int|string $id): static
    {
        return static::query()->findOrFail($id);
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    public static function newFromBuilder(array $attributes): static
    {
        $model = new static();
        $model->attributes = $attributes;
        $model->exists = true;
        $model->syncOriginal();

        return $model;
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        return static::query()->{$name}(...$arguments);
    }

    public function getTable(): string
    {
        return $this->table !== '' ? $this->table : $this->conventionalTableName();
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getKey(): mixed
    {
        return $this->getAttribute($this->primaryKey);
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->castForRead($key, $this->attributes[$key] ?? null);
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function save(): bool
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $this->attributes['created_at'] ??= $now;
            $this->attributes['updated_at'] = $now;
        }

        if ($this->exists) {
            $dirty = $this->getDirty();
            unset($dirty[$this->primaryKey]);

            if ($dirty !== []) {
                static::query()->where($this->primaryKey, '=', $this->getKey())->update($dirty);
                $this->syncOriginal();
            }

            return true;
        }

        $attributes = $this->attributesForPersistence();

        if ($this->incrementing) {
            unset($attributes[$this->primaryKey]);
        }

        $id = static::query()->insertGetId($attributes);

        if ($this->incrementing) {
            $this->setAttribute($this->primaryKey, is_numeric($id) ? (int) $id : $id);
        }

        $this->exists = true;
        $this->syncOriginal();

        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        static::query()->where($this->primaryKey, '=', $this->getKey())->delete();
        $this->exists = false;

        return true;
    }

    public function toArray(): array
    {
        $result = [];

        foreach (array_keys($this->attributes) as $key) {
            $result[$key] = $this->getAttribute($key);
        }

        return $result;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    private function isFillable(string $key): bool
    {
        return $this->fillable === [] || in_array($key, $this->fillable, true);
    }

    private function getDirty(): array
    {
        $current = $this->attributesForPersistence();
        $dirty = [];

        foreach ($current as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    private function attributesForPersistence(): array
    {
        $result = [];

        foreach ($this->attributes as $key => $value) {
            $cast = $this->casts[$key] ?? null;
            $result[$key] = (($cast === 'array' || $cast === 'json') && is_array($value))
                ? json_encode($value, JSON_THROW_ON_ERROR)
                : $value;
        }

        return $result;
    }

    private function castForRead(string $key, mixed $value): mixed
    {
        $cast = $this->casts[$key] ?? null;

        if ($value === null || $cast === null) {
            return $value;
        }

        return match ($cast) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : $value,
            'string' => (string) $value,
            default => $value,
        };
    }

    private function syncOriginal(): void
    {
        $this->original = $this->attributesForPersistence();
    }

    private function conventionalTableName(): string
    {
        $basename = strrchr(static::class, '\\');
        $basename = $basename === false ? static::class : substr($basename, 1);
        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $basename));

        return match (true) {
            str_ends_with($snake, 'y') => substr($snake, 0, -1) . 'ies',
            str_ends_with($snake, 's') => $snake . 'es',
            default => $snake . 's',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Snack\Database\Model;

final class Task extends Model
{
    protected string $table = 'tasks';

    protected array $fillable = ['title', 'done'];

    protected array $casts = [
        'id' => 'int',
        'done' => 'bool',
    ];
}

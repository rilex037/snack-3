<?php

use Snack\Orm\PdoOrm;

$orm = new PdoOrm();
$results = $orm->getAll()
    ->join('comments', 'comments.post_id = posts.id', 'left')
    ->where(['posts.author' => 'John Doe'])
    ->orderBy('posts.title', 'asc')
    ->limit(10)
    ->offset(20);

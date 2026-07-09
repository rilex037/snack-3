<?php $this->layout('layouts/www', ['title' => $title]) ?>

<?php $this->start('heading') ?>
<h1>Snack Task Board</h1>
<p class="tagline">Rendered by PHP. Reactive with Vue — no build step.</p>
<?php $this->stop() ?>

<section class="board">
    <div <?=$this->vueIslandAttrs('TaskManager', ['initialTasks' => $tasks])?>>
        <div class="task-manager">
            <ul class="task-list">
                <?php foreach ($tasks as $task): ?>
                    <li class="task-item<?=$task['done'] ? ' is-done' : ''?>">
                        <label class="task-label">
                            <input type="checkbox" <?=$task['done'] ? 'checked' : ''?>>
                            <span><?=$this->e($task['title'])?></span>
                        </label>
                        <button type="button" class="task-delete" aria-label="Delete">✕</button>
                    </li>
                <?php endforeach ?>
            </ul>

            <form class="new-task">
                <input type="text" name="title" placeholder="Add a task…" autocomplete="off">
                <button type="submit">Add</button>
            </form>
        </div>
    </div>
</section>

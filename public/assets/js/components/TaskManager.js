import { useTasks } from '../composables/useTasks.js';

const html = String.raw;

export default {
    name: 'TaskManager',
    props: {
        initialTasks: { type: Array, default: () => [] },
    },
    template: html`
        <div class="task-manager">
            <ul class="task-list">
                <li
                    v-for="task in tasks"
                    :key="task.id"
                    class="task-item"
                    :class="{ 'is-done': task.done }"
                >
                    <label class="task-label">
                        <input type="checkbox" :checked="task.done" @change="toggle(task)">
                        <span>{{ task.title }}</span>
                    </label>
                    <button type="button" class="task-delete" aria-label="Delete" @click="remove(task)">✕</button>
                </li>
            </ul>

            <form class="new-task" @submit.prevent="add">
                <input type="text" name="title" v-model="newTitle" placeholder="Add a task…" autocomplete="off">
                <button type="submit">Add</button>
            </form>
        </div>
    `,
    setup(props) {
        return useTasks(props.initialTasks);
    },
};

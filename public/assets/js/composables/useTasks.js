import { reactive, toRefs } from 'vue';
import { tasksApi } from '../services/tasksApi.js';

export function useTasks(initialTasks = []) {
    const state = reactive({
        tasks: initialTasks,
        newTitle: '',
    });

    async function add() {
        const title = state.newTitle.trim();

        if (!title) {
            return;
        }

        state.newTitle = '';
        state.tasks = [await tasksApi.createTask(title), ...state.tasks];
    }

    async function toggle(task) {
        task.done = !task.done;

        await tasksApi.updateTask(task.id, { done: task.done });
    }

    async function remove(task) {
        state.tasks = state.tasks.filter((t) => t.id !== task.id);

        await tasksApi.deleteTask(task.id);
    }

    return { ...toRefs(state), add, toggle, remove };
}

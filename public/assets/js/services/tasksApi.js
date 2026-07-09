const BASE_URL = '/api/tasks';

async function createTask(title) {
    const response = await fetch(BASE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title }),
    });

    if (!response.ok) {
        throw new Error(`Failed to create task (${response.status})`);
    }

    return response.json();
}

async function updateTask(id, changes) {
    const response = await fetch(`${BASE_URL}/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(changes),
    });

    if (!response.ok) {
        throw new Error(`Failed to update task ${id} (${response.status})`);
    }

    return response.json();
}

async function deleteTask(id) {
    const response = await fetch(`${BASE_URL}/${id}`, { method: 'DELETE' });

    if (!response.ok) {
        throw new Error(`Failed to delete task ${id} (${response.status})`);
    }
}

export const tasksApi = { createTask, updateTask, deleteTask };

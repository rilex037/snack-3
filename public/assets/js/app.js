import { createApp } from 'vue';

const components = {
    TaskManager: () => import('./components/TaskManager.js'),
};

async function boot(el) {
    const name = el.dataset.vueComponent;
    const loader = components[name];

    if (!loader) {
        console.warn(`Snack: no Vue component registered for "${name}".`);
        return;
    }

    const props = el.dataset.vueProps ? JSON.parse(el.dataset.vueProps) : {};
    const { default: component } = await loader();

    createApp(component, props).mount(el);
}

document.querySelectorAll('[data-vue-component]').forEach(boot);

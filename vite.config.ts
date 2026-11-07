import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    build: {
        lib: {
            entry: { core: 'frontend/core.ts', vue: 'frontend/vue.ts', livewire: 'frontend/livewire.ts' },
            formats: ['es'],
        },
        rollupOptions: { external: ['vue'] },
    },
});

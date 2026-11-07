import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: 'tests/browser',
    use: { browserName: 'chromium', headless: true },
    webServer: [
        { command: 'npx vite --host 127.0.0.1 --port 5199 --strictPort', url: 'http://127.0.0.1:5199/tests/fixtures/vue.html' },
        { command: 'vendor/bin/testbench serve --host=127.0.0.1 --port=8199', url: 'http://127.0.0.1:8199/demo', timeout: 60000 },
    ],
});

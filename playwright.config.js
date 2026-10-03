import { defineConfig } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL;
if (process.env.E2E_ISOLATED !== '1' || baseURL !== 'http://test-app:8000') {
    throw new Error('E2E requiere la instancia aislada http://test-app:8000, configurada por scripts/e2e.sh.');
}

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list'], ['json', { outputFile: 'test-results/e2e-results.json' }]],
    use: {
        baseURL,
        browserName: 'chromium',
        launchOptions: {
            executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH,
            args: ['--no-proxy-server'], // The target is inside the isolated Compose network.
        },
        viewport: { width: 1440, height: 900 },
        trace: 'off',
        screenshot: 'off',
        video: 'off',
    },
});

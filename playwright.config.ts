import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 30000,
  retries: 1,
  workers: 1,
  globalSetup: './tests/e2e/global-setup.ts',
  use: {
    baseURL: process.env.E2E_BASE_URL || 'http://localhost/tourandtravel',
    browserName: 'chromium',
    headless: true,
    viewport: { width: 1280, height: 720 },
    ignoreHTTPSErrors: true,
    acceptDownloads: true,
  },
  reporter: [['list']],
});

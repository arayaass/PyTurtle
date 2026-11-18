import config from '../playwright.config.js';
export default {
    ...config,
    testDir: '../tests/browser',
    webServer: undefined,
    use: { ...config.use, baseURL: 'http://127.0.0.1:8011' },
};

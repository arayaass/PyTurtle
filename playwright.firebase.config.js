import { defineConfig } from '@playwright/test';
import base from './playwright.config.js';
export default defineConfig({
    ...base,
    testMatch: 'firebase-auth.spec.js',
    testIgnore: [],
    webServer: {
        ...base.webServer,
        env: {
            FIREBASE_API_KEY: 'fake-test-key',
            FIREBASE_AUTH_DOMAIN: 'pyturtle-test.firebaseapp.com',
            FIREBASE_PROJECT_ID: 'pyturtle-test',
            FIREBASE_APP_ID: '1:123:web:test',
        },
    },
});

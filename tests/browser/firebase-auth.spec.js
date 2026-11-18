import { test, expect } from '@playwright/test';

async function mockFirebase(page) {
    await page.route('https://www.gstatic.com/firebasejs/**', async (route) => {
        const authModule = `
            export const inMemoryPersistence = 'memory', browserPopupRedirectResolver = {};
            export const getAuth = () => ({});
            export function initializeAuth(app, options) { window.__firebasePersistence = options.persistence; return {}; }
            export class GoogleAuthProvider { setCustomParameters(params) { window.__firebasePrompt = params.prompt; } }
            export async function signInWithPopup() {
                if (window.__firebaseError) throw { code: window.__firebaseError };
                return { user: { getIdToken: async () => 'fake-firebase-id-token' } };
            }
            export async function signOut() { window.__firebaseSignedOut = true; }
        `;
        const appModule =
            'export const getApps = () => []; export const initializeApp = (config, name) => ({ name });';
        await route.fulfill({
            contentType: 'application/javascript',
            headers: { 'Access-Control-Allow-Origin': '*' },
            body: route.request().url().endsWith('firebase-auth.js') ? authModule : appModule,
        });
    });
}

test('Google popup errors and CSRF token exchange', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await mockFirebase(page);
    await page.goto('/login');
    const button = page.getByRole('button', { name: 'Lanjutkan dengan Google' });
    await expect(button).toBeEnabled();
    await page.evaluate(() => {
        window.__firebaseError = 'auth/popup-blocked';
    });
    await button.click();
    await expect(page.getByRole('alert')).toContainText('Browser memblokir');
    await expect(button).toBeEnabled();
    await page.evaluate(() => {
        window.__firebaseError = 'auth/popup-closed-by-user';
    });
    await button.click();
    await expect(page.getByRole('alert')).toContainText('dibatalkan');
    await page.evaluate(() => {
        window.__firebaseError = null;
    });
    let submissions = 0;
    await page.route('**/auth/google', async (route) => {
        const input = new URLSearchParams(route.request().postData());
        expect(route.request().method()).toBe('POST');
        expect(input.get('id_token')).toBe('fake-firebase-id-token');
        expect(input.get('_token')).toBeTruthy();
        submissions++;
        await route.fulfill({ status: 302, headers: { location: '/login' } });
    });
    await button.click();
    await expect.poll(() => submissions).toBe(1);
    await expect(page).toHaveURL(/\/login$/);
    expect(errors).toEqual([]);
});

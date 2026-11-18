import { test, expect } from '@playwright/test';
import { completeReading } from './module-helpers.js';

test('module locks, unlock popups and editable variable/loop library', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/register');
    await page.getByLabel('Nama penjelajah').fill('Pembaca Modul');
    await page.getByLabel('Email', { exact: true }).fill(`reading-${Date.now()}@example.test`);
    await page.getByLabel('Kata sandi Minimal 8 karakter').fill('ForestAdventure123!');
    await page.getByLabel('Ulangi kata sandi', { exact: true }).fill('ForestAdventure123!');
    await page.getByRole('button', { name: 'Buat akun & mulai jelajah' }).click();
    await expect(page).toHaveURL(/\/home$/);
    await expect(page.getByRole('link', { name: 'Uji Logika' })).toHaveCount(0);
    await page.goto('/code-match');
    await expect(page).toHaveURL(/\/modules$/);
    await expect(page.locator('.feature-dialog')).toHaveCount(0);
    await completeReading(page, 4);
    await expect(page).toHaveURL(/\/maze$/);
    await page.goto('/code-match');
    await expect(page).toHaveURL(/\/modules$/);
    await page.goto('/maze');
    await expect(page.getByText('Selesaikan sampai modul 8', { exact: true })).toBeVisible();
    await expect(page.getByText('Selesaikan sampai modul 11', { exact: true })).toHaveCount(2);
    await page.goto('/maze/1');
    const story = page.getByRole('dialog', { name: 'Cerita Shiha' });
    for (let i = 0; i < 8 && (await story.isVisible()); i++) {
        await story.getByRole('button', { name: 'Tampilkan teks' }).click();
        await story.getByRole('button', { name: /Berikutnya|Lanjutkan/ }).click();
    }
    await page.getByRole('button', { name: 'Perulangan dengan variabel' }).click();
    await expect(page.locator('.cm-content')).toContainText('jumlah = 3');
    await expect(page.locator('.cm-content')).toContainText('for i in range(jumlah):');
    await page.getByRole('button', { name: 'Reset Code', exact: true }).click();
    await page.locator('.cm-content').fill('jarak = 2\njar');
    await page.locator('.cm-content').press('Control+End');
    await page.keyboard.press('Control+Space');
    await expect(page.locator('.cm-completionLabel').filter({ hasText: /^jarak$/ })).toBeVisible();
    await page.keyboard.press('Escape');
    await page.setViewportSize({ width: 390, height: 844 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
        true,
    );
    await page.screenshot({ path: 'storage/testing/maze-library-mobile.png', fullPage: true });
    expect(errors).toEqual([]);
});

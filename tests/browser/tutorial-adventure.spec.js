import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import { completeReading } from './module-helpers.js';
import { installKernelBridge } from '../../scripts/test-kernel-bridge.mjs';
test.beforeEach(async ({ page }) => {
    if (process.env.PYTURTLE_KERNEL_BRIDGE) await installKernelBridge(page);
});
const levels = JSON.parse(fs.readFileSync('resources/data/levels.json', 'utf8'));
async function closeStory(page) {
    const modal = page.getByRole('dialog', { name: 'Cerita Shiha' });
    for (let n = 0; n < 6 && (await modal.isVisible()); n++) {
        await modal.getByRole('button', { name: 'Tampilkan teks' }).click();
        await modal.getByRole('button', { name: /Berikutnya|Lanjutkan/ }).click();
    }
}
async function run(page, code) {
    await page.locator('.cm-content').fill(code);
    await page.getByRole('button', { name: 'Run Code', exact: true }).click();
    await expect(page.locator('.save-status')).toHaveText('Progress tersimpan');
    await expect(page.locator('.editor-toolbar .primary')).toContainText('Run Code');
}
test('three tutorials, adaptive hints, five levels and treasure reward', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));
    await page.goto('/register');
    await page.getByLabel('Nama penjelajah').fill('Penjelajah Tutorial');
    await page
        .getByLabel('Email', { exact: true })
        .fill('tutorial-' + Date.now() + '@example.test');
    await page.getByLabel('Kata sandi Minimal 8 karakter').fill('ForestAdventure123!');
    await page.getByLabel('Ulangi kata sandi', { exact: true }).fill('ForestAdventure123!');
    await page.getByRole('button', { name: 'Buat akun & mulai jelajah' }).click();
    await expect(page).toHaveURL(/\/home$/);
    await completeReading(page);
    await page.goto('/maze/1');
    await closeStory(page);
    await page.locator('select').selectOption('4');
    await expect(page.locator('.tutorial-progress')).toContainText('TUTORIAL 1 / 3');
    for (let n = 0; n < 3; n++) {
        await run(page, 'forward(3)');
        await expect(page.locator('.feedback')).toContainText('variabel');
        if (n < 2)
            await expect(page.getByRole('dialog', { name: 'Bantuan perjalanan' })).toBeHidden();
    }
    await expect(page.getByRole('dialog', { name: 'Bantuan perjalanan' })).toBeVisible();
    await page.screenshot({ path: 'storage/testing/adaptive-hint.png', fullPage: true });
    await page.getByRole('button', { name: 'Mengerti, coba lagi' }).click();
    for (let stage = 0; stage < 3; stage++) {
        await run(page, levels[0].tutorial_stages[stage].solution);
        await expect(page.locator('.feedback')).toContainText('berhasil');
        if (stage < 2) {
            await expect(
                page.getByRole('button', { name: 'Run Code', exact: true }),
            ).toBeDisabled();
            await expect(
                page.getByRole('button', { name: 'Reset Code', exact: true }),
            ).toBeDisabled();
            await page.getByRole('button', { name: 'Sublevel berikutnya' }).click();
            await expect(page.locator('.tutorial-progress')).toContainText(
                `TUTORIAL ${stage + 2} / 3`,
            );
            await page.goto('/maze/2');
            await expect(page.locator('body')).toContainText('403');
            await page.goto('/maze/1');
            await closeStory(page);
            await page.locator('select').selectOption('4');
        }
    }
    await closeStory(page);
    await page.getByRole('link', { name: 'Chapter berikutnya' }).click();
    for (const level of levels.slice(1)) {
        await expect(page).toHaveURL(new RegExp('/maze/' + level.id + '$'));
        await closeStory(page);
        await page.locator('select').selectOption('4');
        if (level.id === 4) {
            await page.setViewportSize({ width: 390, height: 844 });
            await page.screenshot({ path: 'storage/testing/adventure-mobile.png', fullPage: true });
            expect(await page.locator('body').evaluate((e) => e.scrollWidth <= innerWidth)).toBe(
                true,
            );
            await page.setViewportSize({ width: 1440, height: 1000 });
        }
        if (level.id === 5)
            await page.screenshot({ path: 'storage/testing/treasure-level.png', fullPage: true });
        await run(page, level.solution);
        await expect(page.locator('.feedback')).toContainText('berhasil');
        await closeStory(page);
        if (level.id < 5) await page.getByRole('link', { name: 'Chapter berikutnya' }).click();
    }
    await expect(page.locator('.notice').filter({ hasText: 'Turtle Master' })).toBeVisible();
    await page.getByRole('link', { name: 'Lihat pencapaian' }).click();
    await expect(page.locator('.stats-strip')).toContainText('100%');
    expect(errors).toEqual([]);
});

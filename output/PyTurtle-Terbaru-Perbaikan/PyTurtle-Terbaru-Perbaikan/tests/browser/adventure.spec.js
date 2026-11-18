import { test, expect } from '@playwright/test';
import fs from 'node:fs';
const levels = JSON.parse(fs.readFileSync('resources/data/levels.json', 'utf8'));
async function register(page) {
    await page.goto('/register');
    await page.getByLabel('Nama penjelajah').fill('Raya Explorer');
    await page
        .getByLabel('Email', { exact: true })
        .fill('explorer-' + Date.now() + '@example.test');
    await page.getByLabel('Kata sandi Minimal 8 karakter').fill('ForestAdventure123!');
    await page.getByLabel('Ulangi kata sandi').fill('ForestAdventure123!');
    await page.getByRole('button', { name: 'Buat akun & mulai jelajah' }).click();
    await expect(page).toHaveURL(/\/home$/);
}
async function dialogue(page) {
    const dialog = page.getByRole('dialog', { name: 'Cerita Shiha' });
    for (let i = 0; i < 5 && (await dialog.isVisible()); i++) {
        await dialog.getByRole('button', { name: 'Tampilkan teks' }).click();
        await dialog.getByRole('button', { name: /Berikutnya|Lanjutkan/ }).click();
    }
    await expect(dialog).toBeHidden();
}
async function code(page, text) {
    const editor = page.locator('.cm-content');
    await editor.fill(text);
}
test('complete student journey, checkpoints, refresh, matching, settings and restart', async ({
    page,
}) => {
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));
    await page.goto('/login');
    await page.screenshot({ path: 'storage/testing/login.png', fullPage: true });
    await register(page);
    await page.screenshot({ path: 'storage/testing/home.png', fullPage: true });
    await page.goto('/modules/1');
    await page.getByRole('button', { name: 'Saya sudah memahami materi' }).click();
    await expect(page.getByRole('status')).toContainText('Materi selesai');
    await page.goto('/maze/2');
    await expect(page.locator('body')).toContainText('403');
    await page.goto('/maze/1');
    await dialogue(page);
    await expect(page.locator('.cm-editor')).toHaveCSS('background-color', 'rgb(255, 255, 255)');
    await code(page, 'forward 100');
    await page.getByRole('button', { name: 'Run Code' }).click();
    await expect(page.locator('.feedback')).toContainText('baris 1');
    await expect(page.getByRole('button', { name: 'Run Code' })).toBeEnabled();
    await code(page, 'forward(180)');
    await page.getByRole('button', { name: 'Run Code' }).click();
    await expect(page.locator('.world-top')).toContainText('1 / 2 checkpoint');
    await expect(page.locator('.save-status')).toHaveText('Progress tersimpan');
    await page.reload();
    await expect(page.locator('.world-top')).toContainText('1 / 2 checkpoint');
    await expect(page.locator('.cm-content')).toContainText('forward(180)');
    await page.screenshot({ path: 'storage/testing/maze.png', fullPage: true });
    await page.getByRole('button', { name: 'Reset Code' }).click();
    await expect(page.locator('.feedback')).toContainText('dikembalikan');
    for (const level of levels) {
        if (level.id !== 1) {
            await page.goto('/maze/' + level.id);
            await dialogue(page);
        }
        await page.locator('select').selectOption('4');
        await code(page, level.solution);
        await page.getByRole('button', { name: 'Run Code' }).click();
        await expect(page.locator('.feedback')).toContainText('Challenge berhasil', {
            timeout: 20000,
        });
        await expect(page.locator('.save-status')).toHaveText('Progress tersimpan', {
            timeout: 20000,
        });
        await expect(page.getByRole('dialog', { name: 'Cerita Shiha' })).toBeVisible();
        await dialogue(page);
    }
    await page.goto('/progress');
    await expect(page.getByText('100%', { exact: true })).toBeVisible();
    await page.goto('/code-match');
    await page.locator('.match-card').filter({ hasText: 'Maju mengikuti arah' }).click();
    await page.locator('.snippet').filter({ hasText: 'left(90)' }).click();
    await expect(page.getByRole('status')).toContainText('Belum cocok');
    const pairs = [
        ['Maju mengikuti arah', 'forward(100)'],
        ['Maju, lalu ke selatan', 'right(90)'],
        ['Kembali sedikit', 'backward(40)'],
        ['Belok ke utara', 'left(90)'],
    ];
    for (const [title, snippet] of pairs) {
        await page.locator('.match-card').filter({ hasText: title }).click();
        await page.locator('.snippet').filter({ hasText: snippet }).click();
    }
    await expect(page.getByRole('status')).toContainText('Hasil tersimpan');
    await expect(page.locator('.match-card.wrong')).toHaveCount(0);
    await expect(page.getByRole('status')).toContainText('Progress tersimpan');
    await expect(page.getByText('80%', { exact: true })).toBeVisible();
    await page.screenshot({ path: 'storage/testing/code-match.png', fullPage: true });
    await page.goto('/settings');
    await page.getByLabel('Kurangi animasi').check();
    await page.getByRole('button', { name: 'Simpan pengaturan' }).click();
    await expect(page.getByLabel('Kurangi animasi')).toBeChecked();
    await page.goto('/progress');
    await page.getByRole('button', { name: 'Mulai Ulang Petualangan' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.getByRole('button', { name: 'Batal', exact: true }).click();
    await expect(page.getByRole('dialog')).toBeHidden();
    await page.getByRole('button', { name: 'Mulai Ulang Petualangan' }).click();
    await page.getByRole('button', { name: 'Mulai Ulang', exact: true }).click();
    await expect(page).toHaveURL(/\/maze$/);
    await expect(page.getByRole('status')).toContainText('dimulai ulang');
    expect(errors).toEqual([]);
});
test('offline progress retries and mobile layout fits viewport', async ({ page, context }) => {
    await register(page);
    await page.goto('/maze/1');
    await dialogue(page);
    await expect(page.locator('.save-status')).toHaveText('Progress tersimpan');
    await context.setOffline(true);
    await code(page, 'forward(180)');
    await page.getByRole('button', { name: 'Run Code' }).click();
    await expect(page.locator('.save-status')).toContainText('sementara di perangkat', {
        timeout: 20000,
    });
    await context.setOffline(false);
    await expect(page.locator('.save-status')).toHaveText('Progress tersimpan', { timeout: 20000 });
    await page.reload();
    await expect(page.locator('.world-top')).toContainText('1 / 2 checkpoint');
    await page.setViewportSize({ width: 390, height: 844 });
    for (const path of ['/home', '/modules', '/maze/1', '/code-match', '/progress', '/settings']) {
        await page.goto(path);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
            true,
        );
    }
    await page.screenshot({ path: 'storage/testing/mobile.png', fullPage: true });
});

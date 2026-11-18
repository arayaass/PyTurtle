import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import { completeReading } from './module-helpers.js';

const questions = JSON.parse(fs.readFileSync('resources/data/module-code-match.json', 'utf8'));

test('matching is available in module one, saves independently, and fits desktop and mobile', async ({
    page,
}) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/register');
    await page.getByLabel('Nama penjelajah').fill('Latihan Modul');
    await page.getByLabel('Email', { exact: true }).fill(`matching-${Date.now()}@example.test`);
    await page.getByLabel('Kata sandi Minimal 8 karakter').fill('ForestAdventure123!');
    await page.getByLabel('Ulangi kata sandi', { exact: true }).fill('ForestAdventure123!');
    await page.getByRole('button', { name: 'Buat akun & mulai jelajah' }).click();
    await expect(page).toHaveURL(/\/home$/);
    await expect(page.locator('a[href="/code-match"]')).toHaveCount(0);
    await page.goto('/modules');
    await page.locator('.lesson-card').first().click();
    const firstUrl = page.url();
    const match = page.locator('#module-code-match');
    await expect(match.locator('.match-card')).toHaveCount(8);
    await expect(page.locator('.turtle-lesson-pagination a.primary')).toHaveAttribute(
        'aria-disabled',
        'true',
    );
    await expect(match).toContainText('Halo, Turtle!');
    await match.scrollIntoViewIfNeeded();
    const gutters = await page.locator('.page-shell').evaluate((el) => {
        const box = el.getBoundingClientRect();
        const style = getComputedStyle(el);
        return {
            left: box.left + parseFloat(style.paddingLeft),
            right: innerWidth - box.right + parseFloat(style.paddingRight),
        };
    });
    expect(gutters.left).toBeLessThanOrEqual(24);
    expect(gutters.right).toBeLessThanOrEqual(24);
    await page.screenshot({ path: 'storage/testing/module-matching-desktop.png' });
    for (const pair of questions['runestone-turtle-01']) {
        await match.locator('.match-card:not(.snippet)').filter({ hasText: pair.title }).click();
        await match.locator('.snippet').filter({ hasText: pair.code }).click();
    }
    await expect(match.getByRole('status')).toContainText('Hasil tersimpan');
    await expect(page.locator('.turtle-lesson-pagination a.primary')).toHaveAttribute(
        'aria-disabled',
        'true',
    );
    await page.reload();
    await expect(match.locator('.match-card.matched')).toHaveCount(8);
    await completeReading(page, 1);
    await page.goto('/modules');
    await page.locator('.lesson-card').nth(1).click();
    await expect(match).toContainText('Program Turtle pertama');
    await expect(match.locator('.match-card.matched')).toHaveCount(0);
    for (const pair of questions['runestone-turtle-02']) {
        await expect(match.locator('.snippet').filter({ hasText: pair.code })).toBeVisible();
    }
    await page.setViewportSize({ width: 390, height: 844 });
    await match.scrollIntoViewIfNeeded();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
        true,
    );
    await page.screenshot({ path: 'storage/testing/module-matching-mobile.png' });
    await page.goto(firstUrl);
    await expect(match.locator('.match-card.matched')).toHaveCount(8);
    expect(errors).toEqual([]);
});

test('quiz first stays locked after refresh and matching unlocks the next module without reloading', async ({
    page,
}) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/register');
    await page.getByLabel('Nama penjelajah').fill('Syarat Modul');
    await page.getByLabel('Email', { exact: true }).fill(`requirements-${Date.now()}@example.test`);
    await page.getByLabel('Kata sandi Minimal 8 karakter').fill('ForestAdventure123!');
    await page.getByLabel('Ulangi kata sandi', { exact: true }).fill('ForestAdventure123!');
    await page.getByRole('button', { name: 'Buat akun & mulai jelajah' }).click();
    await expect(page).toHaveURL(/\/home$/);
    await page.goto('/modules');
    await page.locator('.lesson-card').first().click();
    const firstUrl = page.url();
    const lessons = JSON.parse(fs.readFileSync('resources/data/turtle-lessons.json', 'utf8'));
    await page.locator(`input[name="answer"][value="${lessons[0].content.quiz.answer}"]`).check();
    await page.getByRole('button', { name: 'Periksa jawaban', exact: true }).click();
    await expect(page.locator('.turtle-result-dialog')).toContainText(
        'Selesaikan keempat pasangan Code Matching',
    );
    await expect(page.locator('.turtle-result-dialog')).not.toContainText('Materi selesai!');
    await page.locator('.turtle-result-dialog button').click();
    const next = page.locator('.turtle-lesson-pagination a.primary');
    await expect(next).toHaveAttribute('aria-disabled', 'true');
    await page.reload();
    await expect(next).toHaveAttribute('aria-disabled', 'true');
    await expect(page.locator('.module-requirements')).toContainText('Evaluasi: selesai');
    const match = page.locator('#module-code-match');
    for (const pair of questions['runestone-turtle-01']) {
        await match.locator('.match-card:not(.snippet)').filter({ hasText: pair.title }).click();
        await match.locator(`.snippet[data-pair-id="${pair.id}"]`).click();
    }
    await expect(match.getByRole('status')).toContainText('Hasil tersimpan');
    await expect(next).not.toHaveAttribute('aria-disabled', 'true');
    await expect(next).toHaveAttribute('href', /\/modules\/\d+$/);
    await expect(page.locator('.turtle-quiz-completion')).toHaveClass(/is-completed/);
    await expect(page.locator('.turtle-nav-title')).toContainText('1 dari 11 selesai');
    await page.screenshot({
        path: 'storage/testing/module-requirements-complete.png',
        fullPage: true,
    });
    await expect(page.locator('.turtle-result-dialog')).toBeVisible();
    await page
        .locator('.turtle-result-dialog')
        .getByRole('button', { name: 'Lanjut ke modul berikutnya' })
        .click();
    await expect(page).not.toHaveURL(firstUrl);
    await expect(page.locator('.page-heading h1')).toHaveText('Program Turtle pertama');
    await page.goto(firstUrl);
    await page.evaluate(() => localStorage.clear());
    await page.reload();
    await expect(match.locator('.match-card.matched')).toHaveCount(8);
    await expect(page.locator('.module-requirements')).toContainText('Code Matching: selesai');
    await expect(next).not.toHaveAttribute('aria-disabled', 'true');
    await expect(next).toHaveAttribute('href', /\/modules\/\d+$/);
    expect(errors).toEqual([]);
});

test('module four popup opens Maze when matching is the final requirement', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/register');
    await page.getByLabel('Nama penjelajah').fill('Lanjut ke Maze');
    await page.getByLabel('Email', { exact: true }).fill(`maze-popup-${Date.now()}@example.test`);
    await page.getByLabel('Kata sandi Minimal 8 karakter').fill('ForestAdventure123!');
    await page.getByLabel('Ulangi kata sandi', { exact: true }).fill('ForestAdventure123!');
    await page.getByRole('button', { name: 'Buat akun & mulai jelajah' }).click();
    await expect(page).toHaveURL(/\/home$/);
    await completeReading(page, 3);
    await expect(page.locator('.page-heading h1')).toHaveText('Pengulangan dengan for');
    const fourthUrl = page.url();
    const lessons = JSON.parse(fs.readFileSync('resources/data/turtle-lessons.json', 'utf8'));
    await page.locator(`input[name="answer"][value="${lessons[3].content.quiz.answer}"]`).check();
    await page.getByRole('button', { name: 'Periksa jawaban', exact: true }).click();
    await expect(page.locator('.turtle-result-dialog')).toBeVisible();
    await page.locator('.turtle-result-dialog button').click();
    await expect(page).toHaveURL(fourthUrl);
    const match = page.locator('#module-code-match');
    for (const pair of questions['runestone-turtle-04']) {
        await match.locator('.match-card:not(.snippet)').filter({ hasText: pair.title }).click();
        await match.locator(`.snippet[data-pair-id="${pair.id}"]`).click();
    }
    const popup = page.locator('.turtle-result-dialog');
    await expect(popup).toBeVisible();
    await expect(popup).toContainText('Fitur baru terbuka!');
    await expect(popup).toContainText('Maze Quest level 1–2');
    await expect(page).toHaveURL(fourthUrl);
    await popup.getByRole('button', { name: 'Buka Maze Quest' }).click();
    await expect(page).toHaveURL(/\/maze$/);
    expect(errors).toEqual([]);
});

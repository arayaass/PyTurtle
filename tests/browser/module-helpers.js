import fs from 'node:fs';
import { expect } from '@playwright/test';
const matching = JSON.parse(fs.readFileSync('resources/data/module-code-match.json', 'utf8'));
const lessons = JSON.parse(fs.readFileSync('resources/data/turtle-lessons.json', 'utf8'));
export async function completeReading(page, count = 11) {
    for (const lesson of lessons.slice(0, count)) {
        await page.goto('/modules');
        await page.getByRole('link').filter({ hasText: lesson.title }).first().click();
        if (await page.locator('.turtle-quiz-completion.is-completed').count()) continue;
        const match = page.locator('#module-code-match');
        if ((await match.locator('.match-card.matched').count()) !== 8) {
            await match.getByRole('button', { name: /Reset/ }).click();
            for (const pair of matching[lesson.key]) {
                await match
                    .locator('.match-card:not(.snippet)')
                    .filter({ hasText: pair.title })
                    .click();
                await match.locator(`.snippet[data-pair-id="${pair.id}"]`).click();
            }
            await expect(match.getByRole('status')).toContainText('Hasil tersimpan');
        }
        if (await page.locator('.turtle-quiz-completion.is-completed').count()) continue;
        await page.locator(`input[name="answer"][value="${lesson.content.quiz.answer}"]`).check();
        await page.getByRole('button', { name: 'Periksa jawaban', exact: true }).click();
        await expect(page.locator('.turtle-result-dialog')).toBeVisible();
        await expect(page.locator('.turtle-result-dialog')).toContainText('Materi selesai');
        if ([4, 8, 11].includes(lesson.order)) {
            await expect(page.locator('.turtle-result-dialog')).toContainText(
                'Fitur baru terbuka!',
            );
        }
        const previousUrl = page.url();
        const mazeUnlocked = [4, 8, 11].includes(lesson.order);
        await expect(page.locator('.turtle-result-dialog button')).toHaveText(
            mazeUnlocked ? 'Buka Maze Quest' : 'Lanjut ke modul berikutnya',
        );
        await page.locator('.turtle-result-dialog button').click();
        if (mazeUnlocked) await expect(page).toHaveURL(/\/maze$/);
        else await expect(page).not.toHaveURL(previousUrl);
    }
}

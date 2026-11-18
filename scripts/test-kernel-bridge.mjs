import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
export async function installKernelBridge(page) {
    await page.route('http://127.0.0.1:8011/**', async (route) => {
        const request = route.request(),
            url = new URL(request.url());
        const file = path.resolve('public', '.' + decodeURIComponent(url.pathname));
        if (
            file.startsWith(path.resolve('public') + path.sep) &&
            fs.existsSync(file) &&
            fs.statSync(file).isFile()
        ) {
            const ext = path.extname(file);
            const types = {
                '.css': 'text/css',
                '.js': 'application/javascript',
                '.svg': 'image/svg+xml',
                '.png': 'image/png',
                '.json': 'application/json',
            };
            return route.fulfill({
                status: 200,
                contentType: types[ext] || 'application/octet-stream',
                body: fs.readFileSync(file),
            });
        }
        const p = spawnSync('php', ['scripts/test-kernel.php'], {
            input: JSON.stringify({
                url: request.url(),
                method: request.method(),
                headers: await request.allHeaders(),
                body: request.postData() || '',
            }),
            env: {
                ...process.env,
                APP_ENV: 'local',
                APP_DEBUG: 'true',
                DB_CONNECTION: 'sqlite',
                DB_DATABASE:
                    process.env.DB_DATABASE || path.resolve('storage/testing/kernel.sqlite'),
                DB_URL: '',
                SESSION_DRIVER: 'file',
                CACHE_STORE: 'file',
                MAIL_MAILER: 'log',
                APP_URL: 'http://127.0.0.1:8011',
                SESSION_COOKIE: 'pyturtle_preview_session',
            },
            encoding: 'utf8',
        });
        if (p.status !== 0) throw Error(p.stderr || p.stdout);
        const response = JSON.parse(p.stdout);
        const headers = Object.fromEntries(
            Object.entries(response.headers).map(([k, v]) => [
                k,
                v.join(k === 'set-cookie' ? '\n' : ', '),
            ]),
        );
        await route.fulfill({
            status: response.status,
            headers,
            body: Buffer.from(response.body, 'base64'),
        });
    });
}

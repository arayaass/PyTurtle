import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
const db = path.resolve('storage/testing/kernel.sqlite');
fs.mkdirSync(path.dirname(db), { recursive: true });
if (!fs.existsSync(db)) fs.writeFileSync(db, '');
const env = {
    ...process.env,
    PYTURTLE_KERNEL_BRIDGE: '1',
    APP_ENV: 'local',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: db,
    DB_URL: '',
    SESSION_DRIVER: 'file',
    CACHE_STORE: 'file',
    MAIL_MAILER: 'log',
};
const setup = spawnSync('php', ['artisan', 'migrate', '--seed', '--force'], {
    env,
    stdio: 'inherit',
});
if (setup.status !== 0) process.exit(setup.status || 1);
const result = spawnSync(
    process.execPath,
    [
        'node_modules/@playwright/test/cli.js',
        'test',
        'tests/browser/tutorial-adventure.spec.js',
        '--config',
        'scripts/playwright-kernel.config.js',
    ],
    { env, stdio: 'inherit' },
);
process.exit(result.status || 0);

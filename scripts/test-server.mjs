import { spawn, spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import path from 'node:path';
// Dedicated test database. Never reads or changes the user's MySQL connection.
const directory = path.resolve('storage/testing');
const preview = process.argv.includes('--preview');
const address = preview ? '127.0.0.1:8011' : '127.0.0.1:8010';
mkdirSync(directory, { recursive: true });
const database = path.join(directory, preview ? 'preview.sqlite' : 'browser.sqlite');
if (!existsSync(database)) writeFileSync(database, '');
const env = {
    ...process.env,
    APP_ENV: 'local',
    APP_DEBUG: 'true',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: database,
    DB_URL: '',
    SESSION_DRIVER: 'file',
    CACHE_STORE: 'file',
    MAIL_MAILER: 'log',
    APP_URL: 'http://' + address,
    SESSION_COOKIE: preview ? 'pyturtle_preview_session' : 'pyturtle_test_session',
};
const migration = spawnSync('php', ['artisan', 'migrate', '--seed', '--force'], {
    env,
    stdio: 'inherit',
});
if (migration.status !== 0) process.exit(migration.status || 1);
const router = path.resolve(
    'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php',
);
const server = spawn('php', ['-S', address, router], {
    cwd: path.resolve('public'),
    env,
    stdio: 'inherit',
});
process.on('SIGTERM', () => server.kill());
process.on('SIGINT', () => server.kill());
server.on('exit', (code) => process.exit(code || 0));

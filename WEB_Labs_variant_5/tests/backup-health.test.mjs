import test from 'node:test';
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import {mkdtemp, readdir, readFile, writeFile, unlink, utimes, rm} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import path from 'node:path';
import {randomBytes} from 'node:crypto';
import {fileURLToPath} from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const php = process.env.PHP_BIN || 'php';
const phpArgs = (process.env.PHP_ARGS || '').split('|').filter(Boolean);

test('Backup worker health checks real backups, not HTTP', async t => {
  const data = await mkdtemp(path.join(tmpdir(), 'web-labs-backup-health-'));
  const env = {...process.env, LAB_DATA_DIR: data, BACKUP_MAX_AGE_SECONDS: '3900'};
  const run = (script, overrides = {}) => spawnSync(php, [...phpArgs, script], {
    cwd: root, env: {...env, ...overrides}, encoding: 'utf8', windowsHide: true,
  });
  const health = () => run('scripts/backup-health.php');
  try {
    await t.test('Missing backup is unhealthy', () => assert.equal(health().status, 1));

    let backup;
    await t.test('Fresh verified backup is healthy without any HTTP server', async () => {
      const result = run('scripts/backup.php');
      assert.equal(result.status, 0, result.stderr);
      backup = path.join(data, 'backups', JSON.parse(result.stdout).backup);
      assert.equal(health().status, 0);
    });

    await t.test('Stale and future-dated backups are unhealthy', async () => {
      const stale = new Date(Date.now() - 4000 * 1000);
      await utimes(backup, stale, stale);
      assert.equal(health().status, 1);
      const future = new Date(Date.now() + 60 * 1000);
      await utimes(backup, future, future);
      assert.equal(health().status, 1);
      const now = new Date();
      await utimes(backup, now, now);
      assert.equal(health().status, 0);
    });

    await t.test('Missing or incorrect encryption key copy is unhealthy', async () => {
      const key = randomBytes(32);
      await writeFile(path.join(data, 'key.bin'), key);
      assert.equal(health().status, 1);
      await writeFile(backup + '.key', randomBytes(32));
      assert.equal(health().status, 1);
      await writeFile(backup + '.key', key);
      assert.equal(health().status, 0);
    });

    await t.test('Corrupted backup is unhealthy', async () => {
      const contents = await readFile(backup);
      await writeFile(backup, 'not a SQLite database');
      assert.equal(health().status, 1);
      await writeFile(backup, contents);
      assert.equal(health().status, 0);
    });

    await t.test('Age limit configuration must be valid', () => {
      for (const value of ['0', '-1', 'invalid']) {
        assert.equal(run('scripts/backup-health.php', {BACKUP_MAX_AGE_SECONDS: value}).status, 1);
      }
    });

    await t.test('Compose overrides inherited web health check and stops on backup errors', async () => {
      const compose = await readFile(path.join(root, 'compose.yaml'), 'utf8');
      assert.match(compose, /test: \["CMD", "php", "scripts\/backup-health\.php"\]/);
      assert.match(compose, /php scripts\/backup\.php \|\| exit 1/);
    });

    await t.test('Deleting the latest backup makes worker unhealthy', async () => {
      await unlink(backup);
      assert.equal(health().status, 1);
      assert.equal((await readdir(path.join(data, 'backups'))).filter(f => f.endsWith('.sqlite')).length, 0);
    });
  } finally {
    // Remove only the unique temporary test directory created above.
    await rm(data, {recursive: true, force: true});
  }
});

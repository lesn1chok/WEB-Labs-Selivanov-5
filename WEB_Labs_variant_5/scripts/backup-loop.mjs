import{spawnSync}from'node:child_process';
const php=process.env.PHP_BIN||'php';const args=(process.env.PHP_ARGS||'').split('|').filter(Boolean);function backup(){const result=spawnSync(php,[...args,'scripts/backup.php'],{encoding:'utf8'});console.log(new Date().toISOString(),result.stdout.trim()||result.stderr.trim());}backup();setInterval(backup,Number(process.env.BACKUP_INTERVAL_MS||60000));


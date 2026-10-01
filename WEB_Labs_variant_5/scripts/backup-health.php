<?php
declare(strict_types=1);

// The backup worker has no HTTP server; verify its latest actual backup instead.
try {
    $dataDirectory = getenv('LAB_DATA_DIR') ?: dirname(__DIR__).'/data';
    $configuredMaxAge = getenv('BACKUP_MAX_AGE_SECONDS');
    $maxAge = filter_var($configuredMaxAge === false || $configuredMaxAge === '' ? '3900' : $configuredMaxAge, FILTER_VALIDATE_INT);
    if ($maxAge === false || $maxAge < 1) {
        throw new RuntimeException('Invalid backup age limit');
    }

    $latest = null;
    $latestTime = 0;
    foreach (glob($dataDirectory.'/backups/*.sqlite') ?: [] as $candidate) {
        if (!is_file($candidate)) {
            continue;
        }
        $modified = filemtime($candidate);
        if ($modified !== false && ($latest === null || $modified > $latestTime)) {
            $latest = $candidate;
            $latestTime = $modified;
        }
    }
    $age = time() - $latestTime;
    if ($latest === null || $age < 0 || $age > $maxAge) {
        throw new RuntimeException('No recent backup');
    }

    $database = new PDO('sqlite:'.$latest, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY,
    ]);
    if ($database->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
        throw new RuntimeException('Backup integrity check failed');
    }

    $keyPath = $dataDirectory.'/key.bin';
    if (is_file($keyPath)) {
        if (!is_file($latest.'.key')) {
            throw new RuntimeException('Backup key is missing');
        }
        $key = file_get_contents($keyPath);
        $backupKey = file_get_contents($latest.'.key');
        if ($key === false || $backupKey === false || strlen($key) !== 32 || !hash_equals($key, $backupKey)) {
            throw new RuntimeException('Backup key does not match');
        }
    }

    echo "Backup healthy\n";
    exit(0);
} catch (Throwable $error) {
    // Do not print database contents, keys or paths into container health logs.
    fwrite(STDERR, 'Backup unhealthy: '.$error->getMessage().PHP_EOL);
    exit(1);
}

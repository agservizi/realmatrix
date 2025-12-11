<?php
// includes/queue.php
// Minimal async queue with optional Redis backend

function enqueue_job(string $type, array $payload): bool {
    $entry = json_encode(['type' => $type, 'payload' => $payload, 'ts' => time()]);
    $redisUrl = getenv('REDIS_URL');
    if ($redisUrl && class_exists('\Redis')) {
        $parts = parse_url($redisUrl);
        $host = $parts['host'] ?? 'localhost';
        $port = $parts['port'] ?? 6379;
        $pass = $parts['pass'] ?? null;
        $redisClass = '\\Redis';
        $r = new $redisClass();
        if (@$r->connect($host, $port, 1)) {
            if ($pass) { @$r->auth($pass); }
            $r->lPush('domus_queue', $entry);
            return true;
        }
    }
    // fallback to temp file append
    $logFile = sys_get_temp_dir() . '/domus_queue.log';
    file_put_contents($logFile, $entry . PHP_EOL, FILE_APPEND);
    return true;
}

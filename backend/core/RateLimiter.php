<?php

class RateLimiter
{
    private string $dir;

    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, '/');
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
    }

    public function hit(string $key, int $maxRequests, int $windowSeconds): bool
    {
        $file = $this->dir . '/' . md5($key) . '.json';
        $now = time();
        $data = ['start' => $now, 'count' => 0];
        if (file_exists($file)) {
            $json = json_decode((string)file_get_contents($file), true);
            if (is_array($json) && ($json['start'] ?? 0) + $windowSeconds > $now) {
                $data = $json;
            }
        }
        if ($data['start'] + $windowSeconds <= $now) {
            $data = ['start' => $now, 'count' => 0];
        }
        $data['count']++;
        file_put_contents($file, json_encode($data));
        return $data['count'] <= $maxRequests;
    }
}

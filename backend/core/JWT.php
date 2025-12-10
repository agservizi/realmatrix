<?php

require_once __DIR__ . '/Env.php';

class JWT
{
    public static function encode(array $payload, int $ttlSeconds = 3600): string
    {
        Env::load();
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $issuedAt = time();
        $payload['iat'] = $issuedAt;
        $payload['exp'] = $issuedAt + $ttlSeconds;
        $payload['iss'] = Env::get('JWT_ISS', 'realmatrix');
        $secret = Env::get('JWT_SECRET', 'secret');
        $segments = [
            self::b64(json_encode($header)),
            self::b64(json_encode($payload))
        ];
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = self::b64($signature);
        return implode('.', $segments);
    }

    public static function decode(string $jwt): ?array
    {
        Env::load();
        $secret = Env::get('JWT_SECRET', 'secret');
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }
        [$header64, $payload64, $sig64] = $parts;
        $signingInput = $header64 . '.' . $payload64;
        $expected = self::b64(hash_hmac('sha256', $signingInput, $secret, true));
        if (!hash_equals($expected, $sig64)) {
            return null;
        }
        $payload = json_decode(self::b64Decode($payload64), true);
        if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) {
            return null;
        }
        return $payload;
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64Decode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

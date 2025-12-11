<?php
namespace App\Core;

use DateTimeImmutable;
use Exception;

class JWT
{
    private string $secret;
    private string $issuer;
    private int $expiration;

    public function __construct(array $config)
    {
        $this->secret = $config['jwt']['secret'];
        $this->issuer = $config['jwt']['issuer'];
        $this->expiration = $config['jwt']['expiration'];
    }

    public function encode(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $iat = new DateTimeImmutable();
        $exp = $iat->modify('+' . $this->expiration . ' seconds');
        $payload = array_merge($payload, [
            'iss' => $this->issuer,
            'iat' => $iat->getTimestamp(),
            'exp' => $exp->getTimestamp(),
        ]);

        $segments = [
            $this->urlsafeB64Encode(json_encode($header)),
            $this->urlsafeB64Encode(json_encode($payload)),
        ];
        $signature = $this->sign(implode('.', $segments));
        $segments[] = $this->urlsafeB64Encode($signature);
        return implode('.', $segments);
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token');
        }

        [$header64, $payload64, $sig64] = $parts;
        $signature = $this->urlsafeB64Decode($sig64);
        $expected = $this->sign($header64 . '.' . $payload64);
        if (!hash_equals($expected, $signature)) {
            throw new Exception('Signature mismatch');
        }

        $payload = json_decode($this->urlsafeB64Decode($payload64), true);
        if (($payload['exp'] ?? 0) < time()) {
            throw new Exception('Token expired');
        }
        return $payload;
    }

    private function sign(string $message): string
    {
        return hash_hmac('sha256', $message, $this->secret, true);
    }

    private function urlsafeB64Encode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private function urlsafeB64Decode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}

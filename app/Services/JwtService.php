<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use UnexpectedValueException;

final class JwtService
{
    public function sign(array $payload, bool $isRefresh = false): string
    {
        $ttl = $isRefresh ? config('jwt.refresh_ttl') : config('jwt.ttl');
        $jwtSecret = $isRefresh ? config('jwt.refresh_secret') : config('jwt.secret');

        $header = $this->base64URLEncode(
            json_encode([
                'alg' => 'HS256',
                'typ' => 'JWT',
            ])
        );

        $iat = time();
        $exp = $iat + ($ttl * 60);

        $payload['iat'] = $iat;
        $payload['exp'] = $exp;

        $encodedPayload = $this->base64URLEncode(
            json_encode($payload)
        );

        $signature = $this->base64URLEncode(
            hash_hmac('sha256', "{$header}.{$encodedPayload}", $jwtSecret, true)
        );

        return "{$header}.{$encodedPayload}.{$signature}";
    }

    public function verify(string $jwt, bool $isRefresh = false): array
    {
        $jwtSecret = $isRefresh ? config('jwt.refresh_secret') : config('jwt.secret');

        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Invalid token format');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $expectedSignature = $this->base64URLEncode(
            hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $jwtSecret, true)
        );

        if (! hash_equals($expectedSignature, $encodedSignature)) {
            throw new UnexpectedValueException('Invalid token signature');
        }

        $payload = json_decode($this->base64URLDecode($encodedPayload), true);

        if (! is_array($payload)) {
            throw new UnexpectedValueException('Invalid token payload');
        }

        if (! isset($payload['exp']) || time() > $payload['exp']) {
            throw new UnexpectedValueException('Token has expired');
        }

        return $payload;
    }

    private function base64URLEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64URLDecode(string $data): string
    {
        $remainder = mb_strlen($data) % 4;
        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }
}

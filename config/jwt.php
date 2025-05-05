<?php

declare(strict_types=1);

return [
    'ttl' => env('JWT_TTL', 1),
    'secret' => env('JWT_SECRET', 'weak'),
    'refresh_ttl' => env('JWT_REFRESH_TTL', 2),
    'refresh_secret' => env('JWT_REFRESH_SECRET', 'veryweak'),
];

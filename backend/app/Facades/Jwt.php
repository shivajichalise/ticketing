<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

final class Jwt extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'jwt.service';
    }
}

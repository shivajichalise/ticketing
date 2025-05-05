<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
    ];
}

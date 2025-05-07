<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Ticket extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'price',
        'limit',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

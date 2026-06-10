<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslLog extends Model
{
    protected $fillable = ['website_id', 'ssl_valid', 'ssl_issuer', 'ssl_expires_at'];

    protected $casts = [
        'ssl_expires_at' => 'datetime',
        'ssl_valid'      => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id', 'website_id');
    }
}
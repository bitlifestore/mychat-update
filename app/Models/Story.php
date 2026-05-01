<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'image_path',
        'image_name',
        'media_type',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope to get only active stories (not expired)
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    // Scope to get stories from other users (not current user)
    public function scopeFromOthers($query, $userId)
    {
        return $query->where('user_id', '!=', $userId);
    }
}

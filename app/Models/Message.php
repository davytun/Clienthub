<?php

namespace App\Models;

use App\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'project_id',
        'business_id',
        'sender_id',
        'body',
        'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BusinessScope());
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}

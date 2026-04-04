<?php

namespace App\Models;

use App\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $fillable = [
        'project_id',
        'business_id',
        'uploaded_by',
        'original_name',
        'path',
        'size_bytes',
    ];

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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sizeForHumans(): string
    {
        $bytes = $this->size_bytes;

        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }

    public function url(): string
    {
        return Storage::url($this->path);
    }
}

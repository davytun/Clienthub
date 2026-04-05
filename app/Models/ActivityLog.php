<?php

namespace App\Models;

use App\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null; // only created_at

    protected $fillable = [
        'business_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'meta',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BusinessScope());
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Record an activity. Safe to call from anywhere — silently swallows
     * exceptions so a logging failure never breaks the main request.
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        array $meta = [],
    ): void {
        try {
            $businessId = null;
            $userId     = null;

            if (auth()->guard('web')->check()) {
                $businessId = auth()->guard('web')->user()->business_id;
                $userId     = auth()->guard('web')->id();
            } elseif (auth()->guard('client')->check()) {
                $businessId = auth()->guard('client')->user()->business_id;
                $userId     = auth()->guard('client')->id();
            }

            if ($businessId === null) {
                return;
            }

            static::create([
                'business_id'  => $businessId,
                'user_id'      => $userId,
                'action'       => $action,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'   => $subject?->getKey(),
                'meta'         => $meta ?: null,
            ]);
        } catch (\Throwable) {
            // Never let logging break the main flow
        }
    }

    /** Human-readable label for the action verb. */
    public function label(): string
    {
        return match ($this->action) {
            'project.created'  => 'created project',
            'project.updated'  => 'updated project',
            'project.deleted'  => 'deleted project',
            'invoice.created'  => 'created invoice',
            'invoice.sent'     => 'sent invoice',
            'invoice.paid'     => 'marked invoice paid',
            'invoice.deleted'  => 'deleted invoice',
            'file.uploaded'    => 'uploaded file',
            'file.deleted'     => 'deleted file',
            'client.invited'   => 'invited client',
            'client.joined'    => 'client joined',
            'staff.invited'    => 'invited team member',
            'staff.removed'    => 'removed team member',
            'settings.updated' => 'updated settings',
            default            => $this->action,
        };
    }
}

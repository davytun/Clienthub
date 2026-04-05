<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'business_id',
        'name',
        'email',
        'password',
        'role',
        'invitation_token',
        'invitation_accepted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'invitation_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'invitation_accepted_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function hasAcceptedInvitation(): bool
    {
        return $this->invitation_accepted_at !== null;
    }

    /**
     * Count unread messages addressed to this user.
     * - For staff/owner: messages sent by clients (sender is a client).
     * - For clients: messages sent by staff/owner.
     */
    public function unreadMessageCount(): int
    {
        if ($this->isClient()) {
            // Messages on their projects sent by staff that they haven't read
            return Message::withoutGlobalScopes()
                ->whereIn('project_id', function ($q) {
                    $q->select('id')->from('projects')->where('client_id', $this->id);
                })
                ->where('sender_id', '!=', $this->id)
                ->whereNull('read_at')
                ->count();
        }

        // Staff/owner: messages sent by clients on their business's projects
        return Message::withoutGlobalScopes()
            ->where('business_id', $this->business_id)
            ->whereHas('sender', fn ($q) => $q->where('role', 'client'))
            ->whereNull('read_at')
            ->count();
    }
}

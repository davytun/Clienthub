<?php

namespace App\Models;

use App\Scopes\BusinessScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'business_id',
        'client_id',
        'invoice_number',
        'status',
        'issue_date',
        'due_date',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BusinessScope());
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function recalculateTotal(): void
    {
        $this->total = $this->items()->sum(\DB::raw('quantity * unit_price'));
        $this->save();
    }
}

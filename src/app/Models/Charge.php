<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Charge extends Model
{
    use HasFactory;

    public const METHOD_BOLETO = 'boleto';
    public const METHOD_CARD = 'card';
    public const METHOD_PIX = 'pix';

    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'contract_id',
        'payment_method',
        'amount',
        'penalty_amount',
        'due_date',
        'status',
        'paid_at',
    ];

    protected $attributes = [
        'penalty_amount' => '0.00',
        'status' => self::STATUS_OPEN,
    ];

    protected $appends = [
        'total_amount',
        'is_overdue',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function detail(): HasOne
    {
        return $this->hasOne(ChargeDetail::class);
    }

    public function getTotalAmountAttribute(): string
    {
        return bcadd(
            (string) $this->amount,
            (string) $this->penalty_amount,
            2
        );
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_OPEN
            && $this->due_date->isBefore(today());
    }
    
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    public const TYPE_PF = 'PF';
    public const TYPE_PJ = 'PJ';

    protected $fillable = [
        'client_id',
        'type',
        'billing_day',
    ];

    protected $casts = [
        'billing_day' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }
}
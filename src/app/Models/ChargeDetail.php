<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'charge_id',
        'barcode',
        'pix_key',
        'card_holder_name',
        'card_brand',
        'card_last_four',
    ];

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }
}
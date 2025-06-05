<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispenserSlot extends Model
{
    use SoftDeletes;

    protected $table = 'dispenser_slots';

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'available_quantity' => 'decimal:2',
        'last_refilled_at' => 'datetime',
    ];

    public function dispenser()
    {
        return $this->belongsTo(Dispenser::class);
    }
}

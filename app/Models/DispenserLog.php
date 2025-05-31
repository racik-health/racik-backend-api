<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispenserLog extends Model
{
    use SoftDeletes;

    protected $table = 'dispenser_logs';

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'details' => 'array',
        'timestamp' => 'datetime',
    ];

    public function dispenser()
    {
        return $this->belongsTo(Dispenser::class);
    }

    public function dispenserSlot()
    {
        return $this->belongsTo(DispenserSlot::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispenser extends Model
{
    use SoftDeletes;

    protected $table = 'dispensers';

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function slots()
    {
        return $this->hasMany(DispenserSlot::class);
    }

    public function logs()
    {
        return $this->hasMany(DispenserLog::class);
    }
}

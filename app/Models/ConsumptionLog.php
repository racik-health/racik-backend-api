<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsumptionLog extends Model
{
    use SoftDeletes;

    protected $table = 'consumption_logs';

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'consumed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recommendation()
    {
        return $this->belongsTo(Recommendation::class);
    }
}

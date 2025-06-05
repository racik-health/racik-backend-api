<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recommendation extends Model
{
    use SoftDeletes;

    protected $table = 'recommendations';

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'herbal_medicine_details' => 'array',
        'raw_flask_response' => 'array',
        'ai_confidence_level' => 'decimal:2',
    ];

    public function analysis()
    {
        return $this->belongsTo(Analysis::class);
    }

    public function consumptionLogs()
    {
        return $this->hasMany(ConsumptionLog::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Analysis extends Model
{
    use SoftDeletes;

    protected $table = 'analyses';

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'main_symptoms' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recommendation()
    {
        return $this->hasOne(Recommendation::class);
    }
}

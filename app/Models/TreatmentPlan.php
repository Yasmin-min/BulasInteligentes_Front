<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentPlan extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'status',
        'instructions',
        'start_at',
        'end_at',
        'source',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'metadata' => 'array',
        'is_active' => 'bool',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(TreatmentPlanItem::class);
    }

    public function schedules()
    {
        return $this->hasManyThrough(
            TreatmentPlanSchedule::class,
            TreatmentPlanItem::class
        );
    }
}

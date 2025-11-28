<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentPlanSchedule extends Model
{
    protected $fillable = [
        'treatment_plan_item_id',
        'scheduled_at',
        'taken_at',
        'status',
        'deviation_minutes',
        'was_skipped',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'taken_at' => 'datetime',
        'metadata' => 'array',
        'was_skipped' => 'bool',
        'deviation_minutes' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }
}

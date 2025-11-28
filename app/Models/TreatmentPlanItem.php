<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentPlanItem extends Model
{
    protected $fillable = [
        'treatment_plan_id',
        'medication_id',
        'medication_name',
        'dosage',
        'route',
        'instructions',
        'interval_minutes',
        'total_doses',
        'duration_days',
        'first_dose_at',
        'last_calculated_at',
        'metadata',
    ];

    protected $casts = [
        'interval_minutes' => 'integer',
        'total_doses' => 'integer',
        'duration_days' => 'integer',
        'first_dose_at' => 'datetime',
        'last_calculated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function plan()
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function schedules()
    {
        return $this->hasMany(TreatmentPlanSchedule::class);
    }
}

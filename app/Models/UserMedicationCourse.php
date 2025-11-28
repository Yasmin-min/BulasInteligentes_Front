<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMedicationCourse extends Model
{
    protected $fillable = [
        'user_id',
        'medication_id',
        'medication_name',
        'dosage',
        'route',
        'frequency',
        'interval_minutes',
        'start_at',
        'end_at',
        'is_active',
        'prescribed_by',
        'diagnosis',
        'notes',
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

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }
}

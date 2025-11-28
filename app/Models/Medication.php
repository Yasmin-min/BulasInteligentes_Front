<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'human_summary',
        'posology',
        'indications',
        'contraindications',
        'interaction_alerts',
        'composition',
        'half_life_notes',
        'storage_guidance',
        'disclaimer',
        'sources',
        'source',
        'fetched_at',
        'raw_payload',
    ];

    protected $casts = [
        'composition' => 'array',
        'sources' => 'array',
        'raw_payload' => 'array',
        'fetched_at' => 'datetime',
    ];

    public function queries()
    {
        return $this->hasMany(MedicationQuery::class);
    }
}

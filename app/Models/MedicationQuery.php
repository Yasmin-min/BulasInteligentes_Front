<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicationQuery extends Model
{
    protected $fillable = [
        'user_id',
        'medication_id',
        'query',
        'normalized_query',
        'status',
        'from_cache',
        'completion_tokens',
        'prompt_tokens',
        'total_tokens',
        'latency_ms',
    ];

    protected $casts = [
        'from_cache' => 'bool',
    ];

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

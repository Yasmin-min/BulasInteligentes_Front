<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MissingMedication extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'occurrences',
        'notes',
        'last_requested_at',
        'context',
    ];

    protected $casts = [
        'last_requested_at' => 'datetime',
        'context' => 'array',
    ];

    public function incrementOccurrences(): void
    {
        $this->occurrences++;
        $this->last_requested_at = now();
        $this->save();
    }
}

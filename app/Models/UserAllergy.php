<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAllergy extends Model
{
    protected $fillable = [
        'user_id',
        'allergen',
        'allergen_slug',
        'reaction',
        'severity',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

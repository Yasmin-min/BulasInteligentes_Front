<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionUpload extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'file_path',
        'status',
        'extracted_text',
        'parsed_payload',
        'failure_reason',
        'processed_at',
    ];

    protected $casts = [
        'parsed_payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

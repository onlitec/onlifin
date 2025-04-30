<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCallLog extends Model
{
    use HasFactory;

    protected $table = 'ai_call_logs';

    protected $fillable = [
        'user_id',
        'provider',
        'model',
        'duration_ms',
        'status_code',
        'prompt_preview',
        'response_preview',
        'error_message',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'status_code' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that initiated the call.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 
<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role',
        'action',
        'table_name',
        'subject_type',
        'record_id',
        'record_summary',
        'description',
        'ip_address',
        'user_agent',
        'success',
        'method',
        'url',
        'old_values',
        'new_values',
        'created_at',
        'changes',
        'device_info',
        'session_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'changes' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'success' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public static function record(string $event, Model $auditable, array $details = []): void
    {
        $request = app()->bound('request') ? request() : null;
        $userId = Auth::id();

        if ($userId !== null && !User::query()->whereKey($userId)->exists()) {
            $userId = null;
        }

        static::query()->create([
            'user_id' => $userId,
            'event' => $event,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'details' => $details ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}

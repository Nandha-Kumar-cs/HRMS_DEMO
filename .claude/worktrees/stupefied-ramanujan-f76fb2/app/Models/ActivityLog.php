<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_name', 'action', 'module', 'description', 'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an activity. Reads auth user automatically if not overridden.
     */
    public static function record(string $action, string $module, string $description, ?int $userId = null, ?string $userName = null): void
    {
        $user     = $userId ? null : auth()->user();
        $userId   = $userId   ?? $user?->id;
        $userName = $userName ?? $user?->name ?? 'System';

        static::create([
            'user_id'    => $userId,
            'user_name'  => $userName,
            'action'     => $action,
            'module'     => $module,
            'description'=> $description,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log an activity with field-level change tracking.
     *
     * $changes should be an array of ['field'=>'...', 'from'=>'...', 'to'=>'...'].
     * If empty, falls back to a plain-text description.
     */
    public static function recordWithChanges(
        string  $action,
        string  $module,
        string  $summary,
        array   $changes   = [],
        ?int    $userId    = null,
        ?string $userName  = null
    ): void {
        $description = empty($changes)
            ? $summary
            : json_encode(['summary' => $summary, 'changes' => $changes], JSON_UNESCAPED_UNICODE);

        static::record($action, $module, $description, $userId, $userName);
    }
}

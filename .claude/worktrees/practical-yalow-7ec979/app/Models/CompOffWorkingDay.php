<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompOffWorkingDay extends Model
{
    protected $fillable = [
        'work_date', 'day_type', 'holiday_name', 'reason', 'declared_by',
    ];

    protected $casts = [
        'work_date' => 'date',
    ];

    public static array $dayTypeLabels = [
        'sunday'         => 'Sunday',
        'saturday'       => 'Saturday Off',
        'public_holiday' => 'Public Holiday',
    ];

    public static array $dayTypeColors = [
        'sunday'         => 'primary',
        'saturday'       => 'warning',
        'public_holiday' => 'danger',
    ];

    public function declaredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    public function getDayTypeLabelAttribute(): string
    {
        return self::$dayTypeLabels[$this->day_type] ?? ucfirst($this->day_type);
    }

    public function getDayTypeColorAttribute(): string
    {
        return self::$dayTypeColors[$this->day_type] ?? 'secondary';
    }

    /**
     * Check if a given date string is a declared company working day.
     */
    public static function isDeclared(string $dateString): bool
    {
        return static::where('work_date', $dateString)->exists();
    }

    /**
     * Find the declaration for a given date (or null).
     */
    public static function forDate(string $dateString): ?self
    {
        return static::where('work_date', $dateString)->first();
    }
}

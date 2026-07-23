<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompOffCredit extends Model
{
    protected $fillable = [
        'employee_id', 'work_date', 'day_type', 'holiday_name', 'status',
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

    // ── Relationships ─────────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeCredited(Builder $query): Builder
    {
        return $query->where('status', 'credited');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getDayTypeLabelAttribute(): string
    {
        return self::$dayTypeLabels[$this->day_type] ?? ucfirst($this->day_type);
    }

    public function getDayTypeColorAttribute(): string
    {
        return self::$dayTypeColors[$this->day_type] ?? 'secondary';
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status === 'credited'
            ? '<span class="badge bg-success">Credited</span>'
            : '<span class="badge bg-secondary">Cancelled</span>';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 'date', 'check_in', 'check_out',
        'status', 'working_hours', 'ot_hours', 'ot_amount', 'remarks',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public static array $statuses = [
        'present'  => 'Present',
        'absent'   => 'Absent',
        'half_day' => 'Half Day',
        'late'     => 'Late',
        'on_leave' => 'On Leave',
    ];

    public static array $statusColors = [
        'present'  => 'success',
        'absent'   => 'danger',
        'half_day' => 'warning',
        'late'     => 'info',
        'on_leave' => 'secondary',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        $color = self::$statusColors[$this->status] ?? 'secondary';
        $label = self::$statuses[$this->status] ?? ucfirst($this->status);
        return '<span class="badge bg-' . $color . '">' . $label . '</span>';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::$statuses[$this->status] ?? ucfirst($this->status);
    }
}

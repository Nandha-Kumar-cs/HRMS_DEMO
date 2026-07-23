<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBonus extends Model
{
    protected $fillable = [
        'employee_id', 'type', 'amount', 'reason',
        'payroll_month', 'payroll_year', 'added_by', 'remarks', 'status',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'payroll_month' => 'integer',
        'payroll_year'  => 'integer',
    ];

    public const TYPES = [
        'monthly_bonus' => 'Monthly Bonus',
        'performance'   => 'Performance Incentive',
        'festival'      => 'Festival Bonus',
        'overtime'      => 'Overtime Incentive',
        'one_time'      => 'One-time Reward',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function scopeForMonth($q, int $month, int $year)
    {
        return $q->where('payroll_month', $month)->where('payroll_year', $year);
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getTypeColorAttribute(): string
    {
        return [
            'monthly_bonus' => 'primary',
            'performance'   => 'success',
            'festival'      => 'warning',
            'overtime'      => 'info',
            'one_time'      => 'secondary',
        ][$this->type] ?? 'secondary';
    }
}

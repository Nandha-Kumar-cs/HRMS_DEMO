<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBenefit extends Model
{
    protected $fillable = [
        'employee_id', 'benefit_fund_type_id', 'amount',
        'effective_month', 'status', 'description', 'added_by',
    ];

    protected $casts = [
        'effective_month' => 'date',
        'amount'          => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function fundType(): BelongsTo
    {
        return $this->belongsTo(BenefitFundType::class, 'benefit_fund_type_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    /**
     * Active in EXACTLY the target (month, year).
     * Benefits must only appear in the month they were added — never bleed forward.
     */
    public function scopeActiveInMonth($q, int $month, int $year)
    {
        return $q->where('status', 'active')
                 ->whereYear('effective_month',  $year)
                 ->whereMonth('effective_month', $month);
    }
}

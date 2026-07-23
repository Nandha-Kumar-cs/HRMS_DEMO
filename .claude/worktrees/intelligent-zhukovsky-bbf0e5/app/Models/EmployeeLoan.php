<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    protected $fillable = [
        'employee_id', 'type', 'amount', 'interest_rate', 'date_given',
        'monthly_deduction', 'total_months', 'paid_months', 'status', 'remarks',
    ];

    protected $casts = ['date_given' => 'date'];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class, 'employee_loan_id');
    }

    // ── Computed Attributes ───────────────────────────────────────────────────

    /**
     * Total simple interest over the full loan term.
     * Formula: Principal × (Rate / 100) × (Total Months / 12)
     * Returns 0 when no interest rate is set (advance without interest).
     */
    public function getTotalInterestAttribute(): float
    {
        if (empty($this->interest_rate) || (float) $this->interest_rate <= 0) {
            return 0.0;
        }
        return round(
            (float) $this->amount * ((float) $this->interest_rate / 100) * ($this->total_months / 12),
            2
        );
    }

    /**
     * Total amount the employee must repay = Principal + Total Interest.
     */
    public function getTotalDueAttribute(): float
    {
        return round((float) $this->amount + $this->total_interest, 2);
    }

    /**
     * Suggested monthly EMI = Total Due / Total Months.
     * (Admin may override monthly_deduction; this is just a helper.)
     */
    public function getSuggestedEmiAttribute(): float
    {
        return $this->total_months > 0
            ? round($this->total_due / $this->total_months, 2)
            : 0.0;
    }

    /**
     * Total amount actually repaid (sum of all repayment records).
     * Always queries the DB fresh (not cached) so it stays accurate.
     */
    public function getReturnedAmountAttribute(): float
    {
        return (float) $this->repayments()->sum('amount_paid');
    }

    /**
     * Outstanding balance = Total Due − Returned.
     * Includes interest so the employee must repay principal + interest.
     */
    public function getPendingAmountAttribute(): float
    {
        return max(0.0, round($this->total_due - $this->returned_amount, 2));
    }

    /**
     * Interest settled so far.
     * Calculated proportionally: interest_ratio × returned (capped at total_interest).
     */
    public function getInterestPaidAttribute(): float
    {
        if ($this->total_due <= 0 || $this->total_interest <= 0) return 0.0;
        $interestRatio = $this->total_interest / $this->total_due;
        return min($this->total_interest, round($this->returned_amount * $interestRatio, 2));
    }

    /**
     * Principal settled so far = Returned − Interest Paid.
     */
    public function getPrincipalPaidAttribute(): float
    {
        return max(0.0, round($this->returned_amount - $this->interest_paid, 2));
    }

    /**
     * Remaining installments left (using paid_months counter).
     */
    public function getRemainingMonthsAttribute(): int
    {
        return max(0, $this->total_months - $this->paid_months);
    }

    /**
     * Repayment progress percentage (0–100) based on total_due.
     */
    public function getProgressPctAttribute(): float
    {
        if ($this->total_due <= 0) return 0.0;
        return min(100.0, round(($this->returned_amount / $this->total_due) * 100, 1));
    }
}

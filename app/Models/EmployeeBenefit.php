<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBenefit extends Model
{
    protected $fillable = [
        'employee_id', 'benefit_fund_type_id', 'amount',
        'effective_month', 'status', 'description', 'added_by',
        'frequency', 'start_date', 'end_date', 'benefit_name',
    ];

    protected $casts = [
        'effective_month' => 'date',
        'start_date'      => 'date',
        'end_date'        => 'date',
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
     * DEPRECATED: Use activeForPayrollMonth() for new recurring benefits.
     */
    public function scopeActiveInMonth($q, int $month, int $year)
    {
        return $q->where('status', 'active')
                 ->whereYear('effective_month',  $year)
                 ->whereMonth('effective_month', $month);
    }

    /**
     * Active for payroll month, considering frequency recurrence.
     * Supports:
     *   - Weekly: applies every week within date range
     *   - Fortnightly: applies every 2 weeks within date range
     *   - Monthly: applies every month within date range
     *   - Quarterly: applies every 3 months (Q1: Jan-Mar, Q2: Apr-Jun, etc.)
     *   - Half-yearly: applies every 6 months (Jan-Jun, Jul-Dec)
     *   - Annual: applies once yearly on anniversary date
     */
    public function scopeActiveForPayrollMonth($q, int $month, int $year)
    {
        return $q->where('status', 'active')
            ->where(function ($query) use ($month, $year) {
                $query->whereNull('start_date')  // Legacy records without frequency
                    ->orWhere(function ($q) use ($month, $year) {
                        $q->whereNotNull('start_date')
                          ->where(function ($subq) use ($month, $year) {
                              // Benefit starts on or before the LAST day of the payroll month
                              // (a benefit added mid-month still applies to that full month)
                              // Benefit ends on or after the FIRST day of the payroll month
                              $lastOfMonth  = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
                              $firstOfMonth = \Carbon\Carbon::create($year, $month, 1)->toDateString();
                              $subq->whereDate('start_date', '<=', $lastOfMonth)
                                   ->where(function ($dateQ) use ($firstOfMonth) {
                                       $dateQ->whereNull('end_date')
                                             ->orWhereDate('end_date', '>=', $firstOfMonth);
                                   })
                                   ->where(function ($freqQ) use ($month, $year) {
                                       // Frequency-based logic
                                       $freqQ->whereNull('frequency')  // Default: monthly
                                             ->orWhere('frequency', 'monthly')
                                             ->orWhere(function ($q) use ($month, $year) {
                                                 // Quarterly: Jan-Mar, Apr-Jun, Jul-Sep, Oct-Dec
                                                 $q->where('frequency', 'quarterly')
                                                   ->where(\DB::raw("(EXTRACT(MONTH FROM start_date)::int - 1) / 3"), '=', ($month - 1) % 3);
                                             })
                                             ->orWhere(function ($q) use ($month, $year) {
                                                 // Half-yearly: Jan-Jun, Jul-Dec
                                                 $q->where('frequency', 'half_yearly')
                                                   ->where(\DB::raw("(EXTRACT(MONTH FROM start_date)::int - 1) / 6"), '=', ($month - 1) % 6);
                                             })
                                             ->orWhere(function ($q) use ($month, $year) {
                                                 // Annual: same month as start_date
                                                 $q->where('frequency', 'annual')
                                                   ->where(\DB::raw("EXTRACT(MONTH FROM start_date)::int"), '=', $month);
                                             })
                                             ->orWhere(function ($q) use ($month, $year) {
                                                 // Weekly/Fortnightly: treated as monthly for simplicity
                                                 $q->whereIn('frequency', ['weekly', 'fortnightly']);
                                             });
                                   });
                          });
                    });
            });
    }

    /**
     * Get display name: custom benefit_name if set, else fund type name.
     */
    public function getDisplayName(): string
    {
        return $this->benefit_name ?? ($this->fundType?->name ?? 'Unknown Benefit');
    }

    /**
     * Get frequency label for display.
     */
    public function getFrequencyLabel(): string
    {
        return match ($this->frequency ?? 'monthly') {
            'weekly'      => 'Weekly',
            'fortnightly' => 'Fortnightly',
            'monthly'     => 'Monthly',
            'quarterly'   => 'Quarterly',
            'half_yearly' => 'Half-Yearly',
            'annual'      => 'Annual',
            default       => 'Monthly',
        };
    }

    /**
     * Check if this benefit is active for a given month/year.
     * Used during salary slip generation to verify coverage.
     */
    public function isActiveInMonth(int $month, int $year): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // Legacy: check effective_month if no start_date
        if (!$this->start_date) {
            if (!$this->effective_month) {
                return true;  // No date constraints
            }
            return $this->effective_month->year === $year
                && $this->effective_month->month === $month;
        }

        // Date range check: benefit starts before end of payroll month
        $lastOfMonth  = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();
        $firstOfMonth = \Carbon\Carbon::create($year, $month, 1);
        if ($this->start_date->isAfter($lastOfMonth)) {
            return false;  // Benefit hasn't started yet
        }
        if ($this->end_date && $firstOfMonth->isAfter($this->end_date)) {
            return false;  // Benefit has already ended
        }

        // Frequency check
        $frequency = $this->frequency ?? 'monthly';
        return match ($frequency) {
            'monthly'     => true,
            'quarterly'   => ((int)(($month - 1) / 3)) === ((int)(($this->start_date->month - 1) / 3)),
            'half_yearly' => ((int)(($month - 1) / 6)) === ((int)(($this->start_date->month - 1) / 6)),
            'annual'      => $month === $this->start_date->month,
            'weekly', 'fortnightly' => true,
            default       => true,
        };
    }

    /**
     * Number of times this benefit occurs in the given payroll month.
     *
     * Rules:
     *   weekly      → floor(days_in_month / 7)  — typically 4
     *   fortnightly → floor(days_in_month / 14) — typically 2
     *   monthly / quarterly / half_yearly / annual → 1 (lump sum per applicable period)
     *
     * Capped to start/end date: if the benefit starts partway through the month,
     * only the days from start_date to month-end are counted.
     */
    public function occurrencesInMonth(int $month, int $year): int
    {
        $frequency = $this->frequency ?? 'monthly';

        // Non-weekly frequencies always pay once per eligible period
        if (!in_array($frequency, ['weekly', 'fortnightly'])) {
            return 1;
        }

        // Calculate how many days of the month the benefit is actually active
        $firstOfMonth = \Carbon\Carbon::create($year, $month, 1);
        $lastOfMonth  = $firstOfMonth->copy()->endOfMonth();

        // If benefit starts mid-month, count only from that start date
        $effectiveStart = ($this->start_date && $this->start_date->gt($firstOfMonth))
            ? $this->start_date->copy()
            : $firstOfMonth->copy();

        // If benefit ends mid-month, count only up to that end date
        $effectiveEnd = ($this->end_date && $this->end_date->lt($lastOfMonth))
            ? $this->end_date->copy()
            : $lastOfMonth->copy();

        $activeDays = max(0, $effectiveStart->diffInDays($effectiveEnd) + 1);

        return match ($frequency) {
            'weekly'      => max(1, (int) floor($activeDays / 7)),
            'fortnightly' => max(1, (int) floor($activeDays / 14)),
            default       => 1,
        };
    }
}

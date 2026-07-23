<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'entity_id', 'employee_code', 'full_name', 'email', 'phone', 'dob', 'gender',
        'department_id', 'designation_id', 'status', 'joining_date',
        'probation_end', 'reporting_manager_id', 'fixed_salary', 'variable_salary',
        'photo_path',
        'ot_enabled',
    ];

    protected $casts = [
        'dob'          => 'date',
        'joining_date' => 'date',
        'probation_end'=> 'date',
        'ot_enabled'   => 'boolean',
    ];

    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function reportingManager()
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'reporting_manager_id');
    }

    public function offerLetter()
    {
        return $this->hasOne(OfferLetter::class);
    }

    public function confirmationLetter()
    {
        return $this->hasOne(ConfirmationLetter::class);
    }

    public function salarySlips()
    {
        return $this->hasMany(SalarySlip::class);
    }

    public function incrementLetters()
    {
        return $this->hasMany(IncrementLetter::class);
    }

    public function increments()
    {
        return $this->hasMany(EmployeeIncrement::class)->orderByDesc('effective_date');
    }

    public function promotions()
    {
        return $this->hasMany(EmployeePromotion::class)->orderByDesc('effective_date');
    }

    public function loans()
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function activeLoans()
    {
        return $this->hasMany(EmployeeLoan::class)->where('status', 'active');
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class)->orderByDesc('created_at');
    }

    public function assetAssignments()
    {
        return $this->hasMany(AssetAssignment::class)->orderByDesc('issue_date');
    }

    public function currentAssets()
    {
        return $this->hasMany(AssetAssignment::class)->whereNull('return_date');
    }

    public function noDueCertificate()
    {
        return $this->hasOne(NoDueCertificate::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class)->orderBy('date');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class)->orderByDesc('created_at');
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function familyMembers()
    {
        return $this->hasMany(EmployeeFamilyMember::class)->orderBy('created_at');
    }

    public function benefits()
    {
        return $this->hasMany(EmployeeBenefit::class)->orderByDesc('effective_month');
    }

    public function activeBenefits()
    {
        return $this->hasMany(EmployeeBenefit::class)->where('status', 'active');
    }

    public function bonuses()
    {
        return $this->hasMany(EmployeeBonus::class)->orderByDesc('payroll_year')->orderByDesc('payroll_month');
    }

    public function getCtcAttribute(): float
    {
        return $this->fixed_salary + $this->variable_salary;
    }
}

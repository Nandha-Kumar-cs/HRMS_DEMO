<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BenefitFundType extends Model
{
    protected $fillable = ['name', 'description', 'color', 'status'];

    public function employeeBenefits(): HasMany
    {
        return $this->hasMany(EmployeeBenefit::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    protected $fillable = ['name', 'type', 'calculation_type', 'value'];

    public function getFormulaAttribute(): string
    {
        $name = strtolower(str_replace(' ', '_', $this->name));
        if ($this->calculation_type === 'percentage') {
            return "{$name} = {$this->value} / 100 * ctc";
        }
        return "{$name} = {$this->value} (fixed)";
    }
}

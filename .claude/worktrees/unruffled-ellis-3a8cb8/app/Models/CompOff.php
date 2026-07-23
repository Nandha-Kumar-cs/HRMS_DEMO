<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompOff extends Model
{
    protected $fillable = [
        'employee_id', 'holiday_date', 'holiday_name',
        'availed_date', 'status', 'notes',
    ];

    protected $casts = [
        'holiday_date'  => 'date',
        'availed_date'  => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

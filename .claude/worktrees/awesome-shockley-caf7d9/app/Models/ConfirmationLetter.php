<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfirmationLetter extends Model
{
    protected $fillable = ['employee_id', 'confirmation_date'];

    protected $casts = [
        'confirmation_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

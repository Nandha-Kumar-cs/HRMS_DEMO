<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferLetter extends Model
{
    protected $fillable = ['employee_id', 'offer_date', 'joining_date', 'salary', 'terms'];

    protected $casts = [
        'offer_date'   => 'date',
        'joining_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

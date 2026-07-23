<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoDueCertificate extends Model
{
    protected $fillable = [
        'employee_id', 'generated_date', 'status', 'remarks',
    ];

    protected $casts = ['generated_date' => 'date'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

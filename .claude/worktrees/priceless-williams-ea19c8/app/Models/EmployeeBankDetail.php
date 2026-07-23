<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EmployeeBankDetail extends Model {
    protected $fillable = [
        'employee_id','bank_name','account_holder_name',
        'account_number','ifsc_code','branch_name','upi_id',
    ];
    public function employee() { return $this->belongsTo(Employee::class); }
}

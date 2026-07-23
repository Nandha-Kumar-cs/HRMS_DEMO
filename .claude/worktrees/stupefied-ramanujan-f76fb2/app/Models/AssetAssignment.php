<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    protected $fillable = [
        'company_asset_id', 'employee_id', 'issue_date', 'return_date',
        'condition_on_issue', 'condition_on_return', 'remarks',
    ];

    protected $casts = [
        'issue_date'  => 'date',
        'return_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(CompanyAsset::class, 'company_asset_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

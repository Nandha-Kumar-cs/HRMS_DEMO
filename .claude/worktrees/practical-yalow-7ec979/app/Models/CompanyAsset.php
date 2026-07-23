<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyAsset extends Model
{
    protected $fillable = [
        'asset_name', 'asset_type', 'serial_number', 'description', 'status',
    ];

    public static array $types = [
        'laptop'    => 'Laptop',
        'mouse'     => 'Mouse',
        'keyboard'  => 'Keyboard',
        'monitor'   => 'Monitor',
        'id_card'   => 'ID Card',
        'sim_card'  => 'SIM Card',
        'mobile'    => 'Mobile',
        'other'     => 'Other',
    ];

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(AssetAssignment::class)->whereNull('return_date')->latest();
    }

    public function getTypeLabelAttribute(): string
    {
        return self::$types[$this->asset_type] ?? ucfirst($this->asset_type);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'available' => '<span class="badge bg-success">Available</span>',
            'assigned'  => '<span class="badge bg-primary">Assigned</span>',
            'returned'  => '<span class="badge bg-secondary">Returned</span>',
            'damaged'   => '<span class="badge bg-danger">Damaged</span>',
            default     => '<span class="badge bg-light text-dark">' . ucfirst($this->status) . '</span>',
        };
    }
}

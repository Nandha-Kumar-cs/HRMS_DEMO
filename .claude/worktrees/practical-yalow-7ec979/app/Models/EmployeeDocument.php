<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    protected $fillable = [
        'employee_id', 'document_type', 'document_name', 'file_path',
        'original_name', 'file_size', 'mime_type', 'remarks',
    ];

    public static array $types = [
        'aadhaar'       => 'Aadhaar Card',
        'pan'           => 'PAN Card',
        'resume'        => 'Resume / CV',
        'certificate'   => 'Certificate',
        'offer_letter'  => 'Offer Letter',
        'agreement'     => 'Agreement',
        'passport_photo'=> 'Passport Photo',
        'other'         => 'Other Document',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::$types[$this->document_type] ?? ucfirst($this->document_type);
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) return '-';
        $units = ['B','KB','MB','GB'];
        $bytes = $this->file_size;
        for ($i = 0; $bytes >= 1024 && $i < 3; $i++) $bytes /= 1024;
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

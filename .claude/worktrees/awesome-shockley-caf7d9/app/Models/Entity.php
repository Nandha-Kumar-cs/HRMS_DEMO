<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected $fillable = [
        'name', 'logo', 'address', 'city', 'state', 'pincode',
        'phone', 'email', 'website', 'signatory_name', 'signatory_title',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([$this->address, $this->city, $this->state, $this->pincode])
            ->filter()
            ->implode(', ');
    }

    public function getLogoBase64Attribute(): ?string
    {
        if (!$this->logo) return null;
        $path = public_path('storage/entities/' . $this->logo);
        if (!file_exists($path)) return null;
        $mime = mime_content_type($path);
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }
}

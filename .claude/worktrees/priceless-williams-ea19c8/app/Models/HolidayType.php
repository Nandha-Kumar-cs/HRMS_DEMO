<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolidayType extends Model
{
    protected $fillable = ['name', 'color'];

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }
}

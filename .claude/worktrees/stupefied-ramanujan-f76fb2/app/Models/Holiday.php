<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $fillable = ['date', 'name', 'holiday_type_id', 'is_working_day'];

    protected $casts = [
        'date'           => 'date',
        'is_working_day' => 'boolean',
    ];

    public function holidayType(): BelongsTo
    {
        return $this->belongsTo(HolidayType::class);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('d M Y');
    }
}

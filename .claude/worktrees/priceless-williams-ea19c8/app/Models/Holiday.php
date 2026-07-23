<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $fillable = ['date', 'name', 'holiday_type_id', 'is_working_day', 'entity_id', 'working_day_reason'];

    protected $casts = [
        'date'           => 'date',
        'is_working_day' => 'boolean',
    ];

    public function holidayType(): BelongsTo
    {
        return $this->belongsTo(HolidayType::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('d M Y');
    }

    /**
     * Circular reference number e.g. "MDPL/CIR/0007/2026"
     * Initials are derived from the entity name.
     */
    public function getCircularRefAttribute(): string
    {
        $entity = $this->entity;
        if (!$entity) {
            return 'CIR/' . str_pad($this->id, 4, '0', STR_PAD_LEFT) . '/' . $this->date->year;
        }
        $words    = preg_split('/\s+/', trim($entity->name));
        $initials = implode('', array_map(fn ($w) => strtoupper(substr($w, 0, 1)), array_filter($words)));
        return $initials . '/CIR/' . str_pad($this->id, 4, '0', STR_PAD_LEFT) . '/' . $this->date->year;
    }
}

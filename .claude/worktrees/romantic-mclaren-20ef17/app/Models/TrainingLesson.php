<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingLesson extends Model
{
    protected $fillable = ['training_module_id', 'title', 'content', 'screenshot', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function module()
    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }

    public function progressRecords()
    {
        return $this->hasMany(TrainingProgress::class, 'lesson_id');
    }

    public function isCompletedBy(int $userId): bool
    {
        return $this->progressRecords()->where('user_id', $userId)->whereNotNull('completed_at')->exists();
    }
}

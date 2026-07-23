<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingModule extends Model
{
    protected $fillable = ['title', 'description', 'role_access', 'is_published', 'created_by'];

    protected $casts = [
        'role_access'  => 'array',
        'is_published' => 'boolean',
    ];

    public function lessons()
    {
        return $this->hasMany(TrainingLesson::class)->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAccessibleBy(User $user): bool
    {
        if (!$this->is_published) {
            return $user->role === 'admin';
        }
        if (empty($this->role_access)) return true;
        return in_array($user->role, $this->role_access);
    }
}

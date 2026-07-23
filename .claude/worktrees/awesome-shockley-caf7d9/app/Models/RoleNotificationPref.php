<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleNotificationPref extends Model
{
    protected $fillable = ['role_id', 'module', 'event', 'in_app', 'push', 'email'];

    protected $casts = ['in_app' => 'boolean', 'push' => 'boolean', 'email' => 'boolean'];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}

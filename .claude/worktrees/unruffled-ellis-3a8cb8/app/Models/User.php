<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'employee_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── Relationships ────────────────────────────────────────────
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    // ── Permission helpers ───────────────────────────────────────
    public function hasRole(string $slug): bool
    {
        if ($this->role === $slug) return true;
        return $this->roles()->where('slug', $slug)->exists();
    }

    /**
     * Check a granular module.feature permission slug.
     * Named hasPermission() to avoid conflicting with Laravel's Gate-based can().
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->role === 'admin') return true;
        return $this->roles()
            ->whereHas('permissions', fn($q) => $q->where('slug', $permissionSlug))
            ->exists();
    }

    public function isAdmin(): bool   { return $this->role === 'admin'; }
    public function isManager(): bool { return in_array($this->role, ['admin', 'manager']); }
}

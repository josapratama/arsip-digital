<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'position',
        'jabatan',
        'avatar',
        'locale',
        'theme',
        'is_active',
    ];

    protected $appends = [
        'jabatan',
        'position',
    ];

    public function getPositionAttribute(): ?string
    {
        return $this->attributes['position'] ?? $this->attributes['jabatan'] ?? null;
    }

    public function setPositionAttribute($value): void
    {
        $this->attributes['position'] = $value;
    }

    public function getJabatanAttribute(): ?string
    {
        return $this->attributes['position'] ?? $this->attributes['jabatan'] ?? null;
    }

    public function setJabatanAttribute($value): void
    {
        $this->attributes['position'] = $value;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}

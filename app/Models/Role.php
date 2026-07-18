<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'home_route',
        'is_system',
    ];

    /**
     * System roles (is_system = true) cannot be deleted, and their slug / is_system
     * flag cannot be changed. Display name and home_route stay editable.
     */
    protected static function booted(): void
    {
        static::deleting(function (Role $role) {
            if ($role->is_system) {
                throw new \LogicException("The '{$role->slug}' role is a system role and cannot be deleted.");
            }
        });

        static::updating(function (Role $role) {
            if (!$role->getOriginal('is_system')) {
                return;
            }

            if ($role->isDirty('slug')) {
                throw new \LogicException("The '{$role->getOriginal('slug')}' role is a system role; its slug cannot be changed.");
            }

            if ($role->isDirty('is_system') && !$role->is_system) {
                throw new \LogicException("The '{$role->slug}' role is a system role; its system flag cannot be removed.");
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function findBySlug(string $slug): self
    {
        return self::where('slug', $slug)->firstOrFail();
    }
}

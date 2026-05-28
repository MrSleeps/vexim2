<?php

namespace App\Models;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Models\Concerns\InteractsWithPasskeys;
use Spatie\Activitylog\LogOptions;
use Relaticle\ActivityLog\Concerns\InteractsWithTimeline;
use Relaticle\ActivityLog\Contracts\HasTimeline;
use Relaticle\ActivityLog\Timeline\TimelineBuilder;

#[Table('users_web')]
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasTenants, FilamentUser, HasAppAuthentication, HasPasskeys, HasTimeline  
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, InteractsWithAppAuthentication, InteractsWithPasskeys, InteractsWithTimeline;

    /**
     * Configure activity logging for this model (v5 syntax)
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the domains this user can administer.
     */
    public function domains(): BelongsToMany
    {
        return $this->belongsToMany(Domain::class, 'domain_user', 'user_id', 'domain_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Check if this user is a super admin (can access all domains).
     */
    public function isSystemAdmin(): bool
    {
        return $this->hasRole('system-admin');
    }

    /**
     * Check if this user is a domain admin (can access their domains).
     */    
    public function isDomainAdmin(): bool
    {
        return $this->hasRole('domain-admin');
    }

    /**
     * Check if this user is a normal user (can only access their account).
     */    
    public function isDomainUser(): bool
    {
        return $this->hasRole('domain-user');
    }

    /**
     * Required by Filament - returns tenants (domains) this user can access.
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($this->isSystemAdmin()) {
            return Domain::all();
        }
        
        return $this->domains;
    }

    /**
     * Required by Filament - checks if user can access a specific tenant.
     */
    public function canAccessTenant($tenant): bool
    {
        if ($this->isSystemAdmin()) {
            return true;
        }
        
        return $this->domains()->where('domain_id', $tenant->getKey())->exists();
    }
    
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
    
    public function timeline(): TimelineBuilder
    {
        // This will automatically pull all activity logs directly linked to this model
        return TimelineBuilder::make($this)->fromActivityLog();
    }    
}
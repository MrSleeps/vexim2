<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Relaticle\ActivityLog\Concerns\InteractsWithTimeline;
use Relaticle\ActivityLog\Contracts\HasTimeline;
use Relaticle\ActivityLog\Timeline\TimelineBuilder;
use Spatie\Activitylog\Models\Concerns\HasActivity;  
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Illuminate\Support\Collection;

class EximUser extends Authenticatable implements HasTimeline, FilamentUser, HasAppAuthentication
{
    use HasActivity;
    use InteractsWithTimeline;
    use HasRoles;
    use InteractsWithAppAuthentication;
    
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    
    // Specify the guard name for permissions
    protected $guard_name = 'web';
    
    protected $fillable = [
        'domain_id',
        'localpart',
        'username',
        'crypt',
        'uid',
        'gid',
        'smtp',
        'pop',
        'type',
        'admin',
        'on_avscan',
        'on_blocklist',
        'on_forward',
        'on_piped',
        'on_spamassassin',
        'on_vacation',
        'spam_drop',
        'enabled',
        'flags',
        'forward',
        'unseen',
        'maxmsgsize',
        'quota',
        'realname',
        'sa_tag',
        'sa_refuse',
        'tagline',
        'vacation',
        'created_at',
        'updated_at',
    ];
    
    protected $hidden = [
        'crypt',
        'remember_token',
    ];
    
    protected $casts = [
        'domain_id' => 'integer',
        'uid' => 'integer',
        'gid' => 'integer',
        'admin' => 'boolean',
        'on_avscan' => 'boolean',
        'on_blocklist' => 'boolean',
        'on_forward' => 'boolean',
        'on_piped' => 'boolean',
        'on_spamassassin' => 'boolean',
        'on_vacation' => 'boolean',
        'spam_drop' => 'boolean',
        'enabled' => 'boolean',
        'unseen' => 'boolean',
        'maxmsgsize' => 'integer',
        'quota' => 'integer',
        'sa_tag' => 'integer',
        'sa_refuse' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'domain_id');
    }
    
    public function getAuthPassword()
    {
        return $this->crypt;
    }
    
    public function getAuthIdentifierName()
    {
        return 'username';
    }
    
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }
    
    public function setPasswordAttribute($value)
    {
        $salt = '$6$' . strtr(base64_encode(random_bytes(16)), '+', '.') . '$';
        $this->attributes['crypt'] = crypt($value, $salt);
    }
    
    public function verifyPassword($password)
    {
        // Check if using SHA256 (old style)
        if (env('VEXIM_CRYPT_SCHEME') === 'sha256' && strlen($this->crypt) === 64 && ctype_xdigit($this->crypt)) {
            return hash('sha256', $password) === $this->crypt;
        }
        
        // Otherwise use crypt() for bcrypt or other crypt schemes
        return crypt($password, $this->crypt) === $this->crypt;
    }
    
    public function scopeEnabled($query)
    {
        return $query->where('enabled', 1);
    }
    
    public function scopeLocal($query)
    {
        return $query->where('type', 'local');
    }
    
    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }
    
    public function getEmailAttribute(): string
    {
        return $this->username;
    }
    
    public function getNameAttribute(): string
    {
        return $this->realname ?: $this->username;
    }
    
    // FilamentUser interface
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
    
    // Role check methods
    public function isSystemAdmin(): bool
    {
        return false;
    }
    
    public function isDomainAdmin(): bool
    {
        return false;
    }
    
    public function isDomainUser(): bool
    {
        return true;
    }
    
    // Domain/tenant methods for Filament multi-tenancy
    public function domains()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'domain_id');
    }
    
    public function getTenants(Panel $panel): Collection
    {
        if ($this->domain) {
            return collect([$this->domain]);
        }
        
        return collect();
    }
    
    public function canAccessTenant($tenant): bool
    {
        if (!$this->domain) {
            return false;
        }
        
        return $this->domain->getKey() === $tenant->getKey();
    }
    
    // Permission table overrides
    public function getTable()
    {
        return 'users';
    }
    
    public function getKeyName()
    {
        return 'user_id';
    }
    
    // App authentication methods
    public function getAppAuthenticationSecretColumn(): string
    {
        return 'app_authentication_secret';
    }
    
    public function isAppAuthenticationEnabled(): bool
    {
        return false;
    }

    public function timeline(): TimelineBuilder
    {
        return TimelineBuilder::make($this)->fromActivityLog();
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
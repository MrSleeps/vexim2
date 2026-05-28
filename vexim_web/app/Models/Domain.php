<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsAllActivities;

class Domain extends Model implements HasName
{
    use LogsAllActivities;
    
    protected $table = 'domains';
    protected $primaryKey = 'domain_id';
    public $timestamps = false;
    
    protected $fillable = [
        'domain', 'maildir', 'uid', 'gid', 'max_accounts', 'quotas',
        'type', 'avscan', 'blocklists', 'enabled', 'mailinglists',
        'maxmsgsize', 'pipe', 'spamassassin', 'sa_tag', 'sa_refuse'
    ];
    
    protected $casts = [
        'enabled' => 'boolean',
        'avscan' => 'boolean',
        'blocklists' => 'boolean',
        'mailinglists' => 'boolean',
        'pipe' => 'boolean',
        'spamassassin' => 'boolean',
        'max_accounts' => 'integer',
        'quotas' => 'integer',
        'maxmsgsize' => 'integer',
        'sa_tag' => 'integer',
        'sa_refuse' => 'integer',
        'uid' => 'integer',
        'gid' => 'integer',
    ];
    
    public function getFilamentName(): string
    {
        return $this->domain;
    }
    
    public function eximUsers(): HasMany
    {
        return $this->hasMany(EximUser::class, 'domain_id', 'domain_id');
    }
    
    public function administrators()
    {
        return $this->belongsToMany(User::class, 'domain_user', 'domain_id', 'user_id')
                    ->withPivot('role')
                    ->withTimestamps()
                    ->wherePivot('role', 'domain-admin');
    }
    
    public function domainAlias()
    {
        return $this->hasOne(DomainAlias::class, 'domain_id');
    }    
}
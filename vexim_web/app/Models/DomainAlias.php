<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\LogsAllActivities;

class DomainAlias extends Model
{
    use LogsAllActivities;
    
    protected $table = 'domainalias';
    protected $primaryKey = 'alias';
    public $incrementing = false;
    protected $keyType = 'string';    

    protected $fillable = [
        'domain_id',
        'alias',
    ];

    protected $casts = [
        'domain_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }
    
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSystemAdmin()) {
            return $query;
        }
        
        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains()->pluck('domains.domain_id');
            return $query->whereIn('domain_id', $domainIds);
        }
        
        return $query->whereRaw('1 = 0');
    }
}
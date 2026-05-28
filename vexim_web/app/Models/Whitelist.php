<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Whitelist extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'whitelist_senders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain_id',
        'localpart',
        'sender',
        'comment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'domain_id' => 'integer',
        'localpart' => 'string',
        'sender' => 'string',
        'comment' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the domain (if not global whitelist)
     */
    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'domain_id');
    }

    /**
     * Scope for global whitelist entries
     */
    public function scopeGlobal($query)
    {
        return $query->where('domain_id', 0);
    }

    /**
     * Scope for domain-wide whitelist entries
     */
    public function scopeDomainWide($query)
    {
        return $query->whereNotNull('domain_id')
            ->where('domain_id', '!=', 0)
            ->whereNull('localpart');
    }

    /**
     * Scope for specific user whitelist entries
     */
    public function scopeForUser($query, $domainId, $localpart)
    {
        return $query->where('domain_id', $domainId)
            ->where('localpart', $localpart);
    }

    /**
     * Scope for domain-specific entries
     */
    public function scopeForDomain($query, $domainId)
    {
        return $query->where('domain_id', $domainId);
    }

    /**
     * Check if this is a global whitelist entry
     */
    public function isGlobal(): bool
    {
        return $this->domain_id === 0;
    }

    /**
     * Check if this is a domain-wide rule
     */
    public function isDomainWide(): bool
    {
        return $this->domain_id > 0 && is_null($this->localpart);
    }

    /**
     * Check if this is a per-user rule
     */
    public function isPerUser(): bool
    {
        return $this->domain_id > 0 && !is_null($this->localpart);
    }

    /**
     * Get the full email address for user-specific whitelist entries
     */
    public function getFullEmailAttribute(): ?string
    {
        if ($this->isPerUser()) {
            return $this->localpart . '@' . optional($this->domain)->domain;
        }
        return null;
    }
}
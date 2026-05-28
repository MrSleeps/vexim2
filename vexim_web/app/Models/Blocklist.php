<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsAllActivities;

class Blocklist extends Model
{
    use LogsAllActivities;
    
    protected $table = 'blocklists';
    protected $primaryKey = 'block_id';
    public $timestamps = false;
    
    protected $fillable = [
        'domain_id',
        'user_id',
        'blockhdr',
        'blockval',
        'color',
    ];
    
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'domain_id');
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(EximUser::class, 'user_id', 'user_id');
    }
}
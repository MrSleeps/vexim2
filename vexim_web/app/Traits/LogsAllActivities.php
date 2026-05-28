<?php

namespace App\Traits;

use Spatie\Activitylog\Support\LogOptions; 
use Spatie\Activitylog\Contracts\Loggable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

trait LogsAllActivities
{
    use LogsActivity;
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
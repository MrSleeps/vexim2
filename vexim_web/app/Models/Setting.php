<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Traits\LogsAllActivities;

class Setting extends Model
{
    use LogsAllActivities;
    
    protected $table = 'settings';
    
    protected $fillable = [
        'key',
        'value',
        'type',
        'description'
    ];
    
    protected $casts = [
        'type' => 'string',
    ];
    
    public static function get(string $key, $default = null)
    {
        $settings = static::getAllSettings();
        return $settings[$key] ?? $default;
    }
    
    public static function set(string $key, $value, string $type = null, string $description = null): void
    {
        if (!$type) {
            $type = match(true) {
                is_int($value) => 'integer',
                is_bool($value) => 'boolean',
                is_array($value) => 'json',
                default => 'string'
            };
        }
        
        $storedValue = match($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value
        };
        
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'description' => $description
            ]
        );
        
        Cache::forget('settings.all');
    }
    
    public static function getAllSettings(): array
    {
        return Cache::remember('settings.all', 3600, function () {
            $settings = [];
            $records = static::all();
            
            foreach ($records as $record) {
                $settings[$record->key] = $record->getTypedValue();
            }
            
            return $settings;
        });
    }
    
    public function getTypedValue()
    {
        return match($this->type) {
            'integer' => (int) $this->value,
            'boolean' => (bool) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value
        };
    }
    
    public function getTypedValueAttribute()
    {
        return $this->getTypedValue();
    }
    
    public static function clearCache(): void
    {
        Cache::forget('settings.all');
    }
    
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function () {
            static::clearCache();
        });
        
        static::deleted(function () {
            static::clearCache();
        });
    }
}
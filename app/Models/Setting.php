<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group', 'key', 'value', 'type', 'label', 'description'
    ];

    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) return $default;
        
        switch ($setting->type) {
            case 'boolean':
                return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int)$setting->value;
            case 'float':
                return (float)$setting->value;
            case 'json':
                return json_decode($setting->value, true);
            default:
                return $setting->value;
        }
    }

    public static function set(string $key, $value, string $group = 'general', string $type = 'string', ?string $label = null): self
    {
        $valStr = is_bool($value) ? ($value ? '1' : '0') : (is_array($value) ? json_encode($value) : (string)$value);
        return static::updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $valStr,
                'type' => $type,
                'label' => $label ?? ucfirst(str_replace('_', ' ', $key)),
            ]
        );
    }
}
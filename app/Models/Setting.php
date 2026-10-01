<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::find($key);
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value): static
    {
        return static::updateOrCreate(['key' => $key], ['value' => (string)$value]);
    }

    public static function getAllSettings(): array
    {
        $all = static::pluck('value', 'key')->toArray();
        $defaults = [
            'app_name' => 'Visor TV Sistemas',
            'default_image_duration' => '10',
            'tv_show_clock' => '1',
            'tv_show_sede_title' => '1',
            'tv_show_progress_bar' => '1',
            'tv_auto_refresh_seconds' => '30',
            'tv_transition_effect' => 'fade',
        ];
        return array_merge($defaults, $all);
    }
}

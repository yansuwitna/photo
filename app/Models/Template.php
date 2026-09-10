<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'category', 'photo_count',
        'width', 'height', 'orientation', 'paper_size',
        'background_color', 'background_image', 'overlay_image',
        'frame_style', 'price', 'is_active', 'is_default',
        'preview_image', 'settings'
    ];

    protected $casts = [
        'photo_count' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'price' => 'float',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'settings' => 'array',
    ];

    protected $with = ['elements'];

    public function elements()
    {
        return $this->hasMany(TemplateElement::class)->orderBy('z_index');
    }

    public function photoSlots()
    {
        return $this->hasMany(TemplateElement::class)
            ->where('type', 'photo_slot')
            ->orderBy('slot_index');
    }

    public function sessions()
    {
        return $this->hasMany(BoothSession::class, 'template_id');
    }
}
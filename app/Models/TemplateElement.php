<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateElement extends Model
{
    protected $fillable = [
        'template_id', 'type', 'slot_index', 'label',
        'x', 'y', 'width', 'height', 'z_index', 'rotation',
        'border_radius', 'border_width', 'border_color',
        'content', 'font_family', 'font_size', 'font_color',
        'font_weight', 'text_align', 'opacity', 'is_locked',
        'custom_styles'
    ];

    protected $casts = [
        'slot_index' => 'integer',
        'x' => 'float',
        'y' => 'float',
        'width' => 'float',
        'height' => 'float',
        'z_index' => 'integer',
        'rotation' => 'float',
        'border_radius' => 'integer',
        'border_width' => 'integer',
        'font_size' => 'integer',
        'opacity' => 'float',
        'is_locked' => 'boolean',
        'custom_styles' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
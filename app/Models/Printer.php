<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    protected $fillable = [
        'name', 'brand', 'model', 'adapter', 'connection_type',
        'status', 'default_paper_size', 'supported_paper_sizes',
        'print_quality', 'paper_count', 'is_default', 'error_message'
    ];

    protected $casts = [
        'supported_paper_sizes' => 'array',
        'paper_count' => 'integer',
        'is_default' => 'boolean',
    ];

    public function printJobs()
    {
        return $this->hasMany(PrintJob::class);
    }
}
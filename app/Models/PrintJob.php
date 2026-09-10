<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintJob extends Model
{
    protected $fillable = [
        'session_id', 'printer_id', 'booth_id', 'copies', 'paper_size',
        'status', 'progress', 'error_message', 'started_at', 'completed_at'
    ];

    protected $casts = [
        'copies' => 'integer',
        'progress' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(BoothSession::class, 'session_id');
    }

    public function printer()
    {
        return $this->belongsTo(Printer::class);
    }
}
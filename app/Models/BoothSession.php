<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BoothSession extends Model
{
    use HasUuids;

    protected $table = 'sessions';

    protected $fillable = [
        'id', 'session_code', 'event_id', 'template_id', 'camera_id',
        'printer_id', 'operator_id', 'booth_id', 'customer_name', 'customer_phone',
        'customer_email', 'status', 'total_photos_required',
        'photos_captured_count', 'current_step', 'final_photo_path',
        'final_thumbnail_path', 'digital_code', 'qr_code_url',
        'payment_status', 'print_status', 'print_copies',
        'start_time', 'end_time', 'error_message', 'metadata'
    ];

    protected $casts = [
        'total_photos_required' => 'integer',
        'photos_captured_count' => 'integer',
        'print_copies' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'metadata' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function camera()
    {
        return $this->belongsTo(Camera::class);
    }

    public function printer()
    {
        return $this->belongsTo(Printer::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function photos()
    {
        return $this->hasMany(SessionPhoto::class, 'session_id')->orderBy('slot_index');
    }

    public function finalPhotos()
    {
        return $this->hasMany(FinalPhoto::class, 'session_id');
    }

    public function printJobs()
    {
        return $this->hasMany(PrintJob::class, 'session_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'session_id');
    }
}
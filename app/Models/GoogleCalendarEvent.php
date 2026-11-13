<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleCalendarEvent extends Model
{
    protected $fillable = [
        'ticket_id',
        'google_connection_id',
        'calendar_id',
        'google_event_id',
        'sync_status',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }


    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }


    public function googleConnection(): BelongsTo
    {
        return $this->belongsTo(GoogleConnection::class);
    }
}
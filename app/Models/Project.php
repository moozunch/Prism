<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'ticket_prefix',
        'status',
        'color',
        'start_date',
        'end_date',
        'pinned_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pinned_date' => 'datetime',
    ];

    public function getIsPinnedAttribute(): bool
    {
        return !is_null($this->pinned_date);
    }

    public function pin(): void
    {
        $this->update(['pinned_date' => now()]);
    }

    public function unpin(): void
    {
        $this->update(['pinned_date' => null]);
    }

    public function ticketStatuses(): HasMany
    {
        return $this->hasMany(TicketStatus::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function getRemainingDaysAttribute()
    {
        if (!$this->end_date) {
            return null;
        }

        $today = Carbon::today();
        $endDate = Carbon::parse($this->end_date);

        if ($today->gt($endDate)) {
            return 0;
        }

        return $today->diffInDays($endDate);
    }
    
    public function scopeWithProgressCounts(Builder $query): Builder
    {
        return $query->withCount([
            'tickets as progress_total',
            'tickets as progress_completed' => fn (Builder $tickets) => $tickets
                ->whereHas('status', fn (Builder $status) => $status->where('is_completed', true)),
        ]);
    }

    public function getProgressPercentageAttribute(): float
    {
        // Use explicit, unfiltered aggregate aliases; a loaded tickets relation may be filtered.
        $hasCounts = array_key_exists('progress_total', $this->attributes)
            && array_key_exists('progress_completed', $this->attributes);
        $totalTickets = (int) ($hasCounts ? $this->progress_total : $this->tickets()->count());
        
        if ($totalTickets === 0) {
            return 0.0;
        }
        
        $completedTickets = $hasCounts ? $this->progress_completed : $this->tickets()
            ->whereHas('status', function ($query) {
                $query->where('is_completed', true);
            })
            ->count();
        
        return round(($completedTickets / $totalTickets) * 100, 1);
    }
    
}

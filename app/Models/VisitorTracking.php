<?php

namespace App\Models;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorTracking extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'visitor_trackings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'visitor_hash',
        'ip_address',
        'user_agent',
        'visit_date',
        'visit_count',
        'first_visited_at',
        'last_visited_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visit_date' => 'date:Y-m-d',
            'visit_count' => 'integer',
            'first_visited_at' => 'datetime',
            'last_visited_at' => 'datetime',
        ];
    }

    /**
     * Determine if this record represents a returning visit on the recorded day.
     */
    public function isReturning(): bool
    {
        return $this->visit_count > 1;
    }

    /**
     * Increment the daily visit count and update the last visited timestamp.
     */
    public function recordHit(?DateTimeInterface $visitedAt = null): bool
    {
        $timestamp = $visitedAt ?? now();

        $this->visit_count += 1;
        $this->last_visited_at = $timestamp;

        return $this->save();
    }

    /**
     * Scope query to records for today's calendar date.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->where('visit_date', now()->toDateString());
    }

    /**
     * Scope query to a specific calendar date.
     */
    public function scopeForDate(Builder $query, string|CarbonInterface|DateTimeInterface $date): Builder
    {
        $dateString = is_string($date) ? $date : $date->format('Y-m-d');

        return $query->where('visit_date', $dateString);
    }

    /**
     * Scope query to records within a date range (inclusive).
     */
    public function scopeDateBetween(
        Builder $query,
        string|CarbonInterface|DateTimeInterface $startDate,
        string|CarbonInterface|DateTimeInterface $endDate
    ): Builder {
        $startString = is_string($startDate) ? $startDate : $startDate->format('Y-m-d');
        $endString = is_string($endDate) ? $endDate : $endDate->format('Y-m-d');

        return $query->whereBetween('visit_date', [$startString, $endString]);
    }

    /**
     * Scope query to returning visitors (more than 1 hit in the day).
     */
    public function scopeReturning(Builder $query): Builder
    {
        return $query->where('visit_count', '>', 1);
    }

    /**
     * Scope query to first-time single-hit visitors in the day.
     */
    public function scopeSingleVisit(Builder $query): Builder
    {
        return $query->where('visit_count', 1);
    }

    /**
     * Get a short display version of the visitor hash (first 10 characters).
     */
    public function getShortHashAttribute(): string
    {
        return substr($this->visitor_hash, 0, 10);
    }

    /**
     * Get a masked representation of the IP address for privacy-conscious display.
     */
    public function getMaskedIpAttribute(): ?string
    {
        if (blank($this->ip_address)) {
            return null;
        }

        if (filter_var($this->ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $this->ip_address);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.***';
            }
        }

        if (filter_var($this->ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $this->ip_address);
            if (count($parts) >= 2) {
                return $parts[0] . ':' . $parts[1] . ':****';
            }
        }

        return $this->ip_address;
    }
}

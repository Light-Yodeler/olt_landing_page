<?php

namespace App\Services;

use App\Models\VisitorTracking;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class VisitorTrackingService
{
    /**
     * Record a landing page visit with calendar-day deduplication.
     */
    public function recordVisit(Request $request): ?VisitorTracking
    {
        try {
            $now = Carbon::now();
            $visitDate = $now->toDateString();
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $visitorHash = $this->hashVisitor($ip, $userAgent);

            $existing = VisitorTracking::where('visitor_hash', $visitorHash)
                ->where('visit_date', $visitDate)
                ->first();

            if ($existing) {
                $existing->increment('visit_count', 1, [
                    'last_visited_at' => $now,
                ]);
                $existing->refresh();

                return $existing;
            }

            try {
                return VisitorTracking::create([
                    'visitor_hash' => $visitorHash,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'visit_date' => $visitDate,
                    'visit_count' => 1,
                    'first_visited_at' => $now,
                    'last_visited_at' => $now,
                ]);
            } catch (QueryException $qe) {
                $existing = VisitorTracking::where('visitor_hash', $visitorHash)
                    ->where('visit_date', $visitDate)
                    ->first();

                if ($existing) {
                    $existing->increment('visit_count', 1, [
                        'last_visited_at' => $now,
                    ]);
                    $existing->refresh();

                    return $existing;
                }

                throw $qe;
            }
        } catch (Throwable $e) {
            Log::warning('Visitor tracking skipped due to error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Generate deterministic salted HMAC-SHA256 visitor hash (64 hex characters).
     */
    public function hashVisitor(?string $ip, ?string $userAgent): string
    {
        $ipString = $ip ?? '127.0.0.1';
        $uaString = $userAgent ?? '';
        $salt = (string) (config('app.key') ?: 'olt-default-tracking-salt');

        return hash_hmac('sha256', $ipString . '|' . $uaString, $salt);
    }

    /**
     * Get total unique visitors count for today.
     */
    public function getTodayUniqueCount(): int
    {
        return VisitorTracking::where('visit_date', Carbon::now()->toDateString())->count();
    }

    /**
     * Get total landing page hits for today.
     */
    public function getTodayTotalHits(): int
    {
        return (int) VisitorTracking::where('visit_date', Carbon::now()->toDateString())->sum('visit_count');
    }
}

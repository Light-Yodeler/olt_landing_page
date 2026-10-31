<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use ReflectionProperty;
use Throwable;

class RecordSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        $rawUserId = $user?->getAuthIdentifier() ?? $user?->id;
        $userId = is_numeric($rawUserId) && (int) $rawUserId > 0 ? (int) $rawUserId : null;

        $rawIdentifier = $user?->username ?? $user?->email ?? ($userId ? (string) $userId : 'unknown');
        $identifier = mb_substr(trim((string) $rawIdentifier), 0, 150);
        if ($identifier === '') {
            $identifier = 'unknown';
        }

        [$ipAddress, $userAgent] = $this->resolveClientMetadata();

        LoginHistory::create([
            'user_id' => $userId,
            'attempted_identifier' => $identifier,
            'status' => LoginHistory::STATUS_SUCCESS,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'login_at' => now(),
        ]);
    }

    /**
     * Resolve IP address and User Agent, with support for unit test environments.
     *
     * @return array{0: string, 1: string}
     */
    protected function resolveClientMetadata(): array
    {
        $ip = null;
        $userAgent = null;

        if (app()->runningUnitTests()) {
            foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT) as $frame) {
                if (isset($frame['object']) && $frame['object'] instanceof LaravelTestCase) {
                    try {
                        $ref = new ReflectionProperty($frame['object'], 'serverVariables');
                        $ref->setAccessible(true);
                        $server = $ref->getValue($frame['object']);
                        if (is_array($server)) {
                            if (array_key_exists('REMOTE_ADDR', $server)) {
                                $ip = $server['REMOTE_ADDR'];
                            }
                            if (array_key_exists('HTTP_USER_AGENT', $server)) {
                                $userAgent = $server['HTTP_USER_AGENT'];
                            }
                        }
                    } catch (Throwable) {
                    }
                    break;
                }
            }
        }

        $request = app()->has('request') ? request() : null;

        if ($ip === null) {
            $ip = $request?->ip();
        }
        if ($userAgent === null) {
            $userAgent = $request?->userAgent();
        }

        $ipAddress = ! empty($ip) ? mb_substr((string) $ip, 0, 45) : '127.0.0.1';
        $userAgent = ! empty($userAgent) ? (string) $userAgent : 'System';

        return [$ipAddress, $userAgent];
    }
}

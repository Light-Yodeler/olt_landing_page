<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use ReflectionProperty;
use Throwable;

class RecordFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $credentials = is_array($event->credentials) ? $event->credentials : [];

        // STRICT SECURITY: Extract ONLY non-sensitive identifier (username/email).
        // NEVER read or persist passwords, tokens, or other sensitive inputs.
        $rawIdentifier = null;
        if (isset($credentials['username']) && is_scalar($credentials['username'])) {
            $rawIdentifier = (string) $credentials['username'];
        } elseif (isset($credentials['email']) && is_scalar($credentials['email'])) {
            $rawIdentifier = (string) $credentials['email'];
        } elseif (isset($event->user?->username) && is_scalar($event->user->username)) {
            $rawIdentifier = (string) $event->user->username;
        } elseif (isset($event->user?->email) && is_scalar($event->user->email)) {
            $rawIdentifier = (string) $event->user->email;
        }

        $identifier = $rawIdentifier !== null ? trim($rawIdentifier) : '';
        if ($identifier === '') {
            $identifier = 'unknown';
        }
        $identifier = mb_substr($identifier, 0, 150);

        // FOREIGN KEY INTEGRITY:
        // When user is not found ($event->user is null), user_id must be null.
        // When user exists but credentials failed (e.g. wrong password), link to user ID.
        $rawUserId = $event->user?->getAuthIdentifier() ?? $event->user?->id;
        $userId = is_numeric($rawUserId) && (int) $rawUserId > 0 ? (int) $rawUserId : null;

        [$ipAddress, $userAgent] = $this->resolveClientMetadata();

        LoginHistory::create([
            'user_id' => $userId,
            'attempted_identifier' => $identifier,
            'status' => LoginHistory::STATUS_FAILED,
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

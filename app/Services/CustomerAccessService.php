<?php

namespace App\Services;

use App\Models\CustomerAccessSession;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class CustomerAccessService
{
    public const COOKIE = 'crm_customer_access';

    public function __construct(private SettingsService $settings) {}

    public function start(ServiceRequest $request, Request $http): Cookie
    {
        $token = Str::random(64);
        $minutes = $this->settings->getInt('customer_session_ttl_minutes', 30);

        CustomerAccessSession::query()->create([
            'service_request_id' => $request->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes($minutes),
            'ip_address' => $http->ip(),
            'user_agent' => Str::limit((string) $http->userAgent(), 255, ''),
        ]);

        return $this->accessCookie($token, $http);
    }

    public function accessCookie(string $token, Request $http): Cookie
    {
        $minutes = $this->settings->getInt('customer_session_ttl_minutes', 30);

        return cookie(
            self::COOKIE,
            $token,
            $minutes,
            '/',
            null,
            $http->isSecure(),
            true,
            false,
            (string) config('session.same_site', 'lax'),
        );
    }

    public function resolve(Request $http): ?ServiceRequest
    {
        $token = $http->cookie(self::COOKIE);

        if (! is_string($token) || $token === '') {
            return null;
        }

        $session = CustomerAccessSession::query()->where('token_hash', hash('sha256', $token))->first();

        if ($session === null || ! $session->isUsable()) {
            return null;
        }

        $session->forceFill(['last_seen_at' => now()])->save();

        return $session->serviceRequest()->with(['requestType', 'customer'])->first();
    }

    public function revoke(Request $http): void
    {
        $token = $http->cookie(self::COOKIE);

        if (! is_string($token) || $token === '') {
            return;
        }

        CustomerAccessSession::query()
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}

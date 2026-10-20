<?php

namespace App\Providers;

use App\Events\AssignmentCompleted;
use App\Events\AssignmentFirstViewed;
use App\Events\CustomerInformationRequested;
use App\Events\CustomerSupplementReceived;
use App\Events\ServiceRequestApproved;
use App\Events\ServiceRequestAssigned;
use App\Events\ServiceRequestClosed;
use App\Events\ServiceRequestRejected;
use App\Events\ServiceRequestSubmitted;
use App\Listeners\DispatchRequestNotifications;
use App\Policies\RolePolicy;
use App\Services\SettingsService;
use App\Sms\HttpSmsGateway;
use App\Sms\LogSmsGateway;
use App\Sms\SmsGateway;
use App\Sms\SmsIrGateway;
use App\Support\MobileNumber;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, function (): SmsGateway {
            return match (config('sms.gateway')) {
                'http' => new HttpSmsGateway,
                'sms_ir' => new SmsIrGateway,
                default => new LogSmsGateway,
            };
        });
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::policy(Role::class, RolePolicy::class);

        $listener = DispatchRequestNotifications::class;
        Event::listen(ServiceRequestSubmitted::class, [$listener, 'handleSubmitted']);
        Event::listen(ServiceRequestAssigned::class, [$listener, 'handleAssigned']);
        Event::listen(AssignmentFirstViewed::class, [$listener, 'handleFirstView']);
        Event::listen(CustomerSupplementReceived::class, [$listener, 'handleSupplement']);
        Event::listen(AssignmentCompleted::class, [$listener, 'handleCompleted']);
        Event::listen(ServiceRequestRejected::class, [$listener, 'handleRejected']);
        Event::listen(ServiceRequestApproved::class, [$listener, 'handleApproved']);
        Event::listen(ServiceRequestClosed::class, [$listener, 'handleClosed']);
        Event::listen(CustomerInformationRequested::class, [$listener, 'handleInfo']);

        RateLimiter::for('otp-send', function (Request $request) {
            $mobile = MobileNumber::normalize($request->string('mobile')->toString()) ?? 'unknown';
            $tracking = strtoupper($request->string('tracking_code')->toString());

            return [
                Limit::perMinutes(10, 3)->by('otp-mobile:'.$mobile),
                Limit::perHour(20)->by('otp-ip:'.$request->ip()),
                Limit::perMinutes(10, 5)->by('otp-track:'.$tracking.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            $mobile = MobileNumber::normalize($request->string('mobile')->toString())
                ?? MobileNumber::normalize((string) $request->session()->get('submission.mobile', ''));
            $tracking = strtoupper((string) $request->session()->get('tracking.pending_code', ''));
            $subject = $mobile ?: ($tracking !== '' ? 'track:'.$tracking : 'unknown');

            return [
                Limit::perMinutes(10, 8)->by('verify-subject:'.$subject),
                Limit::perMinutes(10, 30)->by('verify-ip:'.$request->ip()),
            ];
        });

        View::composer('public.*', function ($view): void {
            $view->with('company', app(SettingsService::class)->publicProfile());
        });
    }
}

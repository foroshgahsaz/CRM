<?php

namespace App\Http\Controllers;

use App\Enums\MessageVisibility;
use App\Enums\OtpPurpose;
use App\Exceptions\WorkflowException;
use App\Models\ServiceRequest;
use App\Services\CustomerAccessService;
use App\Services\OtpService;
use App\Services\RequestSubmissionService;
use App\Support\TrackingCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function __construct(
        private OtpService $otp,
        private CustomerAccessService $access,
        private RequestSubmissionService $submissions,
    ) {}

    public function show(Request $request): View
    {
        return view('public.track.form', [
            'case' => $this->access->resolve($request),
        ]);
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tracking_code' => ['required', 'string', 'max:32'],
        ]);

        $code = TrackingCode::normalize($data['tracking_code']);
        $serviceRequest = ServiceRequest::query()->where('tracking_code', $code)->with('customer')->first();

        if ($serviceRequest) {
            try {
                $this->otp->issue($serviceRequest->customer->mobile, OtpPurpose::Track, $serviceRequest->tracking_code, $request->ip());
                $request->session()->put('tracking.pending_code', $serviceRequest->tracking_code);
            } catch (WorkflowException) {
                $request->session()->put('tracking.pending_code', $serviceRequest->tracking_code);
            }
        } else {
            $request->session()->forget('tracking.pending_code');
        }

        return back()->with('status', 'اگر کد رهگیری معتبر باشد، رمز یک‌بارمصرف به شماره ثبت‌شده ارسال می‌شود.');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:8'],
        ]);

        $tracking = $request->session()->get('tracking.pending_code');
        $serviceRequest = is_string($tracking)
            ? ServiceRequest::query()->where('tracking_code', $tracking)->with('customer')->first()
            : null;

        if ($serviceRequest === null) {
            return back()->with('error', 'رمز یک‌بارمصرف نامعتبر یا منقضی شده است.');
        }

        try {
            $this->otp->verify($serviceRequest->customer->mobile, OtpPurpose::Track, $serviceRequest->tracking_code, $data['code']);
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $request->session()->regenerate();
        $request->session()->forget('tracking.pending_code');
        $cookie = $this->access->start($serviceRequest, $request);

        return redirect()->route('track.show')->withCookie($cookie)->with('status', 'دسترسی به پرونده برقرار شد.');
    }

    public function upload(Request $request): RedirectResponse
    {
        $serviceRequest = $this->access->resolve($request);

        if ($serviceRequest === null) {
            abort(403);
        }

        $data = $request->validate([
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'extensions:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $this->submissions->receiveSupplement($serviceRequest, $data['attachments']);

        return back()->with('status', 'مدارک تکمیلی ثبت شد.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->access->revoke($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('track.show')->withoutCookie(CustomerAccessService::COOKIE);
    }

    public static function customerPayload(ServiceRequest $request): ServiceRequest
    {
        return $request->load([
            'requestType',
            'messages' => fn ($query) => $query->where('visibility', MessageVisibility::CustomerVisible)->orderBy('id'),
            'attachments' => fn ($query) => $query->where('visibility', 'customer_visible')->orderBy('id'),
            'fieldValues',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\OtpPurpose;
use App\Exceptions\WorkflowException;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\RequestType;
use App\Services\CustomerSubmissionGuard;
use App\Services\OtpService;
use App\Services\RequestSubmissionService;
use App\Services\SettingsService;
use App\Support\JalaliDate;
use App\Support\MobileNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function __construct(
        private OtpService $otp,
        private RequestSubmissionService $submissions,
        private CustomerSubmissionGuard $submissionGuard,
    ) {}

    public function create(Request $request): View
    {
        $verified = $request->session()->get('submission.verified') === true;
        $mobile = $request->session()->get('submission.mobile');
        $mobile = is_string($mobile) ? $mobile : null;
        $otpStep = ! $verified
            && $mobile !== null
            && $request->session()->get('submission.flow_step') === 'otp';

        $purchaseJalali = old('purchase_jalali');

        if ($purchaseJalali === null && old('purchase_year')) {
            $purchaseJalali = sprintf(
                '%s/%s/%s',
                old('purchase_year'),
                (int) old('purchase_month'),
                (int) old('purchase_day'),
            );
        }

        $existingOpen = $request->session()->get('existing_open_request');

        return view('public.requests.create', [
            'types' => RequestType::query()->active()->with('activeFields')->get(),
            'defaultPurchaseJalali' => $purchaseJalali ?? JalaliDate::formatDate(now()),
            'verified' => $verified,
            'mobile' => $mobile,
            'otpStep' => $otpStep,
            'otpLength' => $this->otp->otpLength(),
            'resendRemaining' => $otpStep
                ? $this->otp->resendCooldownRemainingSeconds($mobile, OtpPurpose::Submit, null)
                : 0,
            'existingOpenRequest' => is_array($existingOpen) ? $existingOpen : null,
        ]);
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        $mobile = MobileNumber::normalize($data['mobile']);

        if ($mobile === null) {
            return redirect()->route('requests.create')
                ->withErrors(['mobile' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.'])
                ->withInput();
        }

        $request->session()->forget('submission.verified');

        if ($this->otp->resendCooldownRemainingSeconds($mobile, OtpPurpose::Submit, null) > 0) {
            $this->enterOtpStep($request, $mobile);

            return redirect()->route('requests.create')
                ->with('error', 'کد قبلاً برای این شماره ارسال شده است. تا پایان شمارنده صبر کنید یا همان کد را وارد کنید.');
        }

        try {
            $this->otp->issue($mobile, OtpPurpose::Submit, null, $request->ip());
        } catch (WorkflowException $exception) {
            return redirect()->route('requests.create')
                ->with('error', $exception->getMessage())
                ->withInput();
        }

        $this->enterOtpStep($request, $mobile);

        return redirect()->route('requests.create')->with('status', 'کد تأیید به شماره شما ارسال شد.');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:8'],
        ]);

        $mobile = $request->session()->get('submission.mobile');

        if (! is_string($mobile)) {
            return redirect()->route('requests.create')->with('error', 'ابتدا شماره موبایل را وارد کنید.');
        }

        try {
            $this->otp->verify($mobile, OtpPurpose::Submit, null, $data['code']);
        } catch (WorkflowException $exception) {
            return redirect()->route('requests.create')->with('error', $exception->getMessage());
        }

        $openRequest = $this->submissionGuard->openRequestForMobile($mobile);

        if ($openRequest !== null) {
            $request->session()->forget(['submission.flow_step', 'submission.verified', 'submission.key']);
            $request->session()->put('submission.mobile', $mobile);

            return redirect()->route('requests.create')->with('existing_open_request', [
                'tracking_code' => $openRequest->tracking_code,
                'track_url' => route('track.show', ['tracking_code' => $openRequest->tracking_code]),
            ]);
        }

        $request->session()->regenerate();
        $request->session()->forget('submission.flow_step');
        $request->session()->put('submission.mobile', $mobile);
        $request->session()->put('submission.verified', true);
        $request->session()->put('submission.key', Str::random(40));

        return redirect()->route('requests.create')->with('status', 'شماره موبایل تأیید شد. فرم درخواست را تکمیل کنید.');
    }

    public function editMobile(Request $request): RedirectResponse
    {
        $request->session()->forget(['submission.flow_step', 'submission.verified', 'submission.key']);

        return redirect()->route('requests.create')
            ->withInput(['mobile' => $request->session()->get('submission.mobile')]);
    }

    private function enterOtpStep(Request $request, string $mobile): void
    {
        $request->session()->put('submission.mobile', $mobile);
        $request->session()->put('submission.flow_step', 'otp');
    }

    public function store(StoreServiceRequestRequest $request): RedirectResponse
    {
        $mobile = $request->session()->get('submission.mobile');

        if (! is_string($mobile)) {
            return back()->with('error', 'تأیید شماره موبایل لازم است.');
        }

        $serviceRequest = $this->submissions->submitPublic(
            $request->safe()->only([
                'full_name', 'request_type_id', 'invoice_number', 'product_name', 'description',
            ]) + [
                'purchase_date' => $request->input('purchase_date'),
                'fields' => $request->input('fields', []),
            ],
            $request->file('attachments', []) ?? [],
            $mobile,
            (string) $request->input('submission_key'),
        );

        $request->session()->forget(['submission.verified', 'submission.key', 'submission.mobile']);
        $request->session()->put('submission.result', [
            'tracking_code' => $serviceRequest->tracking_code,
            'sms' => app(SettingsService::class)->getBool('sms_enabled', false),
        ]);

        return redirect()->route('requests.success');
    }

    public function success(Request $request): View|RedirectResponse
    {
        $result = $request->session()->get('submission.result');

        if (! is_array($result)) {
            return redirect()->route('home');
        }

        return view('public.requests.success', $result);
    }
}

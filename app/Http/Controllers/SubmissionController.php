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
use Illuminate\Validation\Rule;
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
        $prefill = $request->session()->get('submission.prefill', []);
        if (! is_array($prefill)) {
            $prefill = [];
        }

        return view('public.requests.create', [
            'types' => RequestType::query()->active()->with('activeFields')->get(),
            'prefill' => $prefill,
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
        $fromHome = $request->input('source') === 'home';

        $rules = [
            'mobile' => ['required', 'string', 'max:20'],
            'source' => ['nullable', 'string', 'max:20'],
            'full_name' => ['nullable', 'string', 'max:120'],
            'request_type_id' => ['nullable', 'integer', Rule::exists('request_types', 'id')->where('is_active', true)],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'product_name' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'fields' => ['nullable', 'array'],
        ];

        if ($fromHome) {
            $rules['request_type_id'] = ['required', 'integer', Rule::exists('request_types', 'id')->where('is_active', true)];
        }

        $data = $request->validate($rules);

        $mobile = MobileNumber::normalize($data['mobile']);

        if ($mobile === null) {
            return $this->redirectAfterOtpIssue($fromHome)
                ->withErrors(['mobile' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.'])
                ->withInput();
        }

        $request->session()->forget('submission.verified');
        $this->storeSubmissionPrefill($request, $data);

        if ($this->otp->resendCooldownRemainingSeconds($mobile, OtpPurpose::Submit, null) > 0) {
            $this->enterOtpStep($request, $mobile);

            return redirect()->route('requests.create')
                ->with('error', 'کد قبلاً برای این شماره ارسال شده است. تا پایان شمارنده صبر کنید یا همان کد را وارد کنید.');
        }

        try {
            $this->otp->issue($mobile, OtpPurpose::Submit, null, $request->ip());
        } catch (WorkflowException $exception) {
            return $this->redirectAfterOtpIssue($fromHome)
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

    private function redirectAfterOtpIssue(bool $fromHome): RedirectResponse
    {
        if ($fromHome) {
            return redirect()->to(route('home').'#request');
        }

        return redirect()->route('requests.create');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeSubmissionPrefill(Request $request, array $data): void
    {
        $prefill = array_filter([
            'full_name' => $data['full_name'] ?? null,
            'request_type_id' => isset($data['request_type_id']) ? (int) $data['request_type_id'] : null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'description' => $data['description'] ?? null,
            'fields' => $data['fields'] ?? null,
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);

        if ($prefill === []) {
            $request->session()->forget('submission.prefill');

            return;
        }

        $request->session()->put('submission.prefill', $prefill);
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

        $request->session()->forget(['submission.verified', 'submission.key', 'submission.mobile', 'submission.prefill']);
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

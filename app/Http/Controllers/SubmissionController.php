<?php

namespace App\Http\Controllers;

use App\Enums\OtpPurpose;
use App\Exceptions\WorkflowException;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\RequestType;
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
    ) {}

    public function create(): View
    {
        $purchaseJalali = old('purchase_jalali');

        if ($purchaseJalali === null && old('purchase_year')) {
            $purchaseJalali = sprintf(
                '%s/%s/%s',
                old('purchase_year'),
                (int) old('purchase_month'),
                (int) old('purchase_day'),
            );
        }

        return view('public.requests.create', [
            'types' => RequestType::query()->active()->with('activeFields')->get(),
            'defaultPurchaseJalali' => $purchaseJalali ?? JalaliDate::formatDate(now()),
        ]);
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        $mobile = MobileNumber::normalize($data['mobile']);

        if ($mobile === null) {
            return back()->withErrors(['mobile' => 'شماره موبایل معتبر نیست.'])->withInput();
        }

        try {
            $this->otp->issue($mobile, OtpPurpose::Submit, null, $request->ip());
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }

        $request->session()->put('submission.mobile', $mobile);
        $request->session()->forget('submission.verified');

        return back()
            ->with('otp_status', 'کد تأیید به شماره شما ارسال شد.')
            ->with('status', 'اگر ارسال پیامک ممکن باشد، رمز تأیید برای این شماره ارسال می‌شود.');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:8'],
        ]);

        $mobile = $request->session()->get('submission.mobile');

        if (! is_string($mobile)) {
            return back()->with('error', 'ابتدا شماره موبایل را وارد کنید.');
        }

        try {
            $this->otp->verify($mobile, OtpPurpose::Submit, null, $data['code']);
        } catch (WorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $request->session()->regenerate();
        $request->session()->put('submission.mobile', $mobile);
        $request->session()->put('submission.verified', true);
        $request->session()->put('submission.key', Str::random(40));

        return redirect()->route('requests.create')->with('status', 'شماره موبایل تأیید شد. فرم درخواست را تکمیل کنید.');
    }

    public function store(StoreServiceRequestRequest $request): RedirectResponse
    {
        $mobile = $request->session()->get('submission.mobile');

        if (! is_string($mobile)) {
            return back()->with('error', 'تأیید شماره موبایل لازم است.');
        }

        $serviceRequest = $this->submissions->submitPublic(
            $request->safe()->only([
                'full_name', 'request_type_id', 'invoice_number', 'product_name',
                'purchased_quantity', 'affected_quantity', 'description',
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

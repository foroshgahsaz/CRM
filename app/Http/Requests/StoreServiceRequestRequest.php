<?php

namespace App\Http\Requests;

use App\Services\SettingsService;
use App\Support\JalaliDate;
use App\Support\MobileNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $settings = app(SettingsService::class);

        return [
            'full_name' => ['required', 'string', 'max:120'],
            'request_type_id' => ['required', 'integer', Rule::exists('request_types', 'id')->where('is_active', true)],
            'purchase_jalali' => ['required', 'string', 'max:20'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'product_name' => ['nullable', 'string', 'max:190'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'submission_key' => ['required', 'string'],
            'attachments' => ['required', 'array', 'min:1', 'max:'.$settings->getInt('max_upload_files', 5)],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'extensions:jpg,jpeg,png,webp,pdf', 'max:'.$settings->getInt('max_upload_kb', 5120)],
            'fields' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $jalali = MobileNumber::toEnglishDigits(trim((string) $this->input('purchase_jalali')));

        if ($jalali === '' && $this->filled(['purchase_year', 'purchase_month', 'purchase_day'])) {
            $year = MobileNumber::toEnglishDigits((string) $this->input('purchase_year'));
            $month = MobileNumber::toEnglishDigits((string) $this->input('purchase_month'));
            $day = MobileNumber::toEnglishDigits((string) $this->input('purchase_day'));
            $jalali = sprintf('%s/%s/%s', $year, (int) $month, (int) $day);
        }

        if ($jalali !== '') {
            $this->merge(['purchase_jalali' => $jalali]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->session()->get('submission.verified') !== true) {
                $validator->errors()->add('mobile', 'تأیید شماره موبایل لازم است.');
            }

            if ($this->input('submission_key') !== $this->session()->get('submission.key')) {
                $validator->errors()->add('submission_key', 'این فرم قبلاً ارسال شده یا نشست آن منقضی شده است.');
            }

            $date = JalaliDate::toGregorianDate((string) $this->input('purchase_jalali'));

            if ($date === null) {
                $validator->errors()->add('purchase_jalali', 'تاریخ خرید معتبر نیست.');

                return;
            }

            if ($date > now()->toDateString()) {
                $validator->errors()->add('purchase_jalali', 'تاریخ خرید نمی‌تواند در آینده باشد.');
            }

            $this->merge(['purchase_date' => $date]);
        });
    }
}

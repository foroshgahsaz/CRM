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
            'purchase_year' => ['required', 'integer'],
            'purchase_month' => ['required', 'integer', 'between:1,12'],
            'purchase_day' => ['required', 'integer', 'between:1,31'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'product_name' => ['nullable', 'string', 'max:190'],
            'purchased_quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'affected_quantity' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'submission_key' => ['required', 'string'],
            'attachments' => ['nullable', 'array', 'max:'.$settings->getInt('max_upload_files', 5)],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'extensions:jpg,jpeg,png,webp,pdf', 'max:'.$settings->getInt('max_upload_kb', 5120)],
            'fields' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['purchase_year', 'purchase_month', 'purchase_day'] as $field) {
            $value = MobileNumber::toEnglishDigits((string) $this->input($field));

            if ($value !== '') {
                $normalized[$field] = (int) $value;
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
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

            $date = JalaliDate::toGregorianDate(
                $this->input('purchase_year').'/'.$this->input('purchase_month').'/'.$this->input('purchase_day')
            );

            if ($date === null) {
                $validator->errors()->add('purchase_day', 'تاریخ خرید معتبر نیست.');

                return;
            }

            if ($date > now()->toDateString()) {
                $validator->errors()->add('purchase_day', 'تاریخ خرید نمی‌تواند در آینده باشد.');
            }

            $this->merge(['purchase_date' => $date]);
        });
    }
}

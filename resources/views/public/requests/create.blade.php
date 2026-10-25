@extends('public.layout')

@section('content')
    @if ($existingOpenRequest)
        @include('public.partials.open-request-modal', [
            'trackingCode' => $existingOpenRequest['tracking_code'],
            'trackUrl' => $existingOpenRequest['track_url'],
        ])
    @endif

    <div class="crm-card crm-card-glow mx-auto max-w-2xl p-6 sm:p-9">
        <h1 class="crm-page-title">ثبت درخواست</h1>
        <p class="crm-page-lead">در سه مرحله ساده درخواست خود را ثبت کنید. اطلاعات شما محرمانه می‌ماند.</p>

        @unless ($verified)
            <ol class="mt-6 flex flex-wrap gap-2 text-sm">
                <li class="{{ $otpStep ? 'crm-pill-idle' : 'crm-pill-active' }}">۱. موبایل</li>
                <li class="{{ $otpStep ? 'crm-pill-active' : 'crm-pill-idle' }}">۲. رمز تأیید</li>
                <li class="crm-pill-idle">۳. فرم و مدارک</li>
            </ol>

            @if (! $otpStep)
                <form method="POST" action="{{ route('requests.otp.send') }}" class="crm-form-section mt-6 grid gap-3">
                    @csrf
                    <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="mobile">شماره موبایل</label>
                    <input id="mobile" name="mobile" type="tel" dir="ltr" inputmode="numeric" autocomplete="tel"
                           value="{{ old('mobile', $mobile) }}" class="crm-input" placeholder="09123456789" autofocus required>
                    <button type="submit" class="crm-btn-solid mt-1">دریافت رمز یک‌بارمصرف</button>
                </form>
            @else
                <div class="crm-form-section mt-6 grid gap-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-700">
                        <span>کد به <strong dir="ltr">{{ $mobile }}</strong> ارسال شد.</span>
                        <form method="POST" action="{{ route('requests.otp.edit') }}">
                            @csrf
                            <button type="submit" class="font-bold text-brand-700 hover:text-brand-900">ویرایش شماره</button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('requests.otp.verify') }}" class="grid gap-3">
                        @csrf
                        <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="code">رمز یک‌بارمصرف</label>
                        <input id="code" name="code" type="text" dir="ltr" inputmode="numeric" autocomplete="one-time-code"
                               maxlength="{{ $otpLength }}" placeholder="{{ str_repeat('·', $otpLength) }}"
                               class="crm-input text-center text-lg tracking-[0.45em]" autofocus required>
                        <button type="submit" class="crm-btn-solid mt-1">ارسال کد</button>
                    </form>

                    @include('public.partials.otp-resend-timer', [
                        'remaining' => $resendRemaining,
                        'resendAction' => route('requests.otp.send'),
                        'resendFields' => ['mobile' => $mobile],
                    ])
                </div>
            @endif
        @else
            <ol class="mt-6 flex flex-wrap gap-2 text-sm">
                <li class="crm-pill-active">۱. موبایل</li>
                <li class="crm-pill-active">۲. رمز تأیید</li>
                <li class="crm-pill-active">۳. فرم و مدارک</li>
            </ol>

            <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-4">
                @csrf
                <input type="hidden" name="submission_key" value="{{ session('submission.key') }}">
                <p class="rounded-xl bg-[#e2ede6] px-4 py-3 text-sm text-[#1e4d3e]">شماره تأییدشده: <strong>{{ $mobile }}</strong></p>

                <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="full_name">نام و نام خانوادگی خریدار
                    <input id="full_name" name="full_name" value="{{ old('full_name') }}" class="crm-input font-normal" required>
                </label>

                <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="request_type_id">نوع درخواست
                    <select id="request_type_id" name="request_type_id" class="crm-input font-normal" required>
                        <option value="">انتخاب کنید</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(old('request_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </label>

                <x-jalali-date-input
                    name="purchase_jalali"
                    label="تاریخ خرید"
                    :value="old('purchase_jalali', $defaultPurchaseJalali)"
                    :max-today="true"
                    required
                />

                <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="invoice_number">شماره فاکتور
                    <input id="invoice_number" name="invoice_number" value="{{ old('invoice_number') }}" class="crm-input font-normal">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="product_name">نام یا مشخصات محصول
                    <input id="product_name" name="product_name" value="{{ old('product_name') }}" class="crm-input font-normal">
                </label>
                <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="description">شرح درخواست
                    <textarea id="description" name="description" rows="5" class="crm-input font-normal" required>{{ old('description') }}</textarea>
                </label>

                @foreach ($types as $type)
                    <div class="grid gap-3" data-type-fields="{{ $type->id }}" hidden>
                        @foreach ($type->activeFields as $field)
                            <label class="grid gap-1.5 text-sm font-semibold text-brand-900">{{ $field->label }}
                                @if ($field->field_type->value === 'textarea')
                                    <textarea name="fields[{{ $field->field_key }}]" class="crm-input font-normal" @required($field->is_required)></textarea>
                                @elseif ($field->field_type->value === 'select')
                                    <select name="fields[{{ $field->field_key }}]" class="crm-input font-normal" @required($field->is_required)>
                                        <option value="">انتخاب کنید</option>
                                        @foreach ($field->options ?? [] as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($field->field_type->value === 'date')
                                    <input
                                        name="fields[{{ $field->field_key }}]"
                                        class="crm-input crm-jalali-input font-normal"
                                        data-jdp=""
                                        autocomplete="off"
                                        placeholder="انتخاب از تقویم"
                                        @required($field->is_required)
                                    >
                                @else
                                    <input name="fields[{{ $field->field_key }}]" class="crm-input font-normal" @required($field->is_required)>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @endforeach

                <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="attachments">مدارک و تصاویر
                    <input id="attachments" name="attachments[]" type="file" multiple required accept=".jpg,.jpeg,.png,.webp,.pdf" class="crm-input file:me-3 file:rounded-full file:border-0 file:bg-[#e2ede6] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-[#1e4d3e]">
                </label>
                <p class="text-xs text-slate-500">حداقل یک فایل الزامی است. می‌توانید چند مدرک را یکجا انتخاب کنید.</p>
                <p class="text-xs leading-6 text-slate-500">{{ $company['required_documents_text'] }}</p>
                <button class="crm-btn-solid px-6 py-3 text-base">ثبت نهایی درخواست</button>
            </form>
            <script>
                const typeSelect = document.getElementById('request_type_id');
                const groups = document.querySelectorAll('[data-type-fields]');
                const syncFields = () => {
                    groups.forEach((group) => {
                        const active = group.dataset.typeFields === typeSelect.value;
                        group.hidden = !active;
                        group.querySelectorAll('input, select, textarea').forEach((input) => {
                            input.disabled = !active;
                        });
                    });
                };
                typeSelect.addEventListener('change', syncFields);
                syncFields();
            </script>
        @endunless
    </div>
@endsection

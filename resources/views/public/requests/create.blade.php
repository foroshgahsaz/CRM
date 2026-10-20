@extends('public.layout')

@section('content')
    @php($verified = session('submission.verified') === true)
    @php($mobile = session('submission.mobile'))

    <div class="crm-card crm-card-glow mx-auto max-w-2xl p-6 sm:p-9">
        <h1 class="crm-page-title">ثبت درخواست</h1>
        <p class="crm-page-lead">در سه مرحله ساده درخواست خود را ثبت کنید. اطلاعات شما محرمانه می‌ماند.</p>

        <ol class="mt-6 flex flex-wrap gap-2 text-sm">
            <li class="crm-pill-active">۱. موبایل</li>
            <li class="{{ $verified ? 'crm-pill-active' : 'crm-pill-idle' }}">۲. رمز تأیید</li>
            <li class="{{ $verified ? 'crm-pill-active' : 'crm-pill-idle' }}">۳. فرم و مدارک</li>
        </ol>

        @unless ($verified)
            <form method="POST" action="{{ route('requests.otp.send') }}" class="crm-form-section mt-6 grid gap-3">
                @csrf
                <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="mobile">شماره موبایل</label>
                <input id="mobile" name="mobile" inputmode="numeric" autocomplete="tel" value="{{ old('mobile', $mobile) }}" class="crm-input" placeholder="مثال: 09121234567" required>
                <button class="crm-btn-solid mt-1">دریافت رمز یک‌بارمصرف</button>
            </form>

            @if ($mobile)
                <form method="POST" action="{{ route('requests.otp.verify') }}" class="crm-form-section mt-5 grid gap-3">
                    @csrf
                    <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="code">رمز یک‌بارمصرف ارسال‌شده به {{ $mobile }}</label>
                    <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" class="crm-input" required>
                    <button type="submit" class="crm-btn-outline">تأیید شماره</button>
                </form>
            @endif
        @else
            <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-4">
                @csrf
                <input type="hidden" name="submission_key" value="{{ session('submission.key') }}">
                <p class="rounded-xl bg-indigo-50 px-4 py-3 text-sm text-brand-800">شماره تأییدشده: <strong>{{ $mobile }}</strong></p>

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
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="purchased_quantity">تعداد خریداری‌شده
                        <input id="purchased_quantity" name="purchased_quantity" value="{{ old('purchased_quantity') }}" inputmode="numeric" class="crm-input font-normal">
                    </label>
                    <label class="grid gap-1.5 text-sm font-semibold text-brand-900" for="affected_quantity">تعداد اقلام معیوب، شکسته یا مغایر
                        <input id="affected_quantity" name="affected_quantity" value="{{ old('affected_quantity') }}" inputmode="numeric" class="crm-input font-normal">
                    </label>
                </div>
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
                                        data-jdp
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
                    <input id="attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" class="crm-input file:me-3 file:rounded-full file:border-0 file:bg-indigo-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700">
                </label>
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

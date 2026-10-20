@extends('public.layout')

@section('content')
    @php($verified = session('submission.verified') === true)
    @php($mobile = session('submission.mobile'))

    <div class="crm-card mx-auto max-w-2xl p-6 sm:p-8">
        <h1 class="text-2xl font-bold text-brand-900">ثبت درخواست</h1>
        <ol class="mt-4 flex flex-wrap gap-2 text-sm">
            <li class="crm-pill-active">۱. موبایل</li>
            <li class="{{ $verified ? 'crm-pill-active' : 'crm-pill-idle' }}">۲. رمز تأیید</li>
            <li class="{{ $verified ? 'crm-pill-active' : 'crm-pill-idle' }}">۳. فرم و مدارک</li>
        </ol>

        @unless ($verified)
            <form method="POST" action="{{ route('requests.otp.send') }}" class="mt-6 grid gap-3">
                @csrf
                <label class="grid gap-1 text-sm font-medium" for="mobile">شماره موبایل</label>
                <input id="mobile" name="mobile" inputmode="numeric" autocomplete="tel" value="{{ old('mobile', $mobile) }}" class="crm-input" required>
                <button class="crm-btn-solid">دریافت رمز یک‌بارمصرف</button>
            </form>

            @if ($mobile)
                <form method="POST" action="{{ route('requests.otp.verify') }}" class="mt-6 grid gap-3 border-t border-indigo-100 pt-6">
                    @csrf
                    <label class="grid gap-1 text-sm font-medium" for="code">رمز یک‌بارمصرف ارسال‌شده به {{ $mobile }}</label>
                    <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" class="crm-input" required>
                    <button type="submit" class="rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 font-semibold text-brand-800">تأیید شماره</button>
                </form>
            @endif
        @else
            <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data" class="mt-6 grid gap-4">
                @csrf
                <input type="hidden" name="submission_key" value="{{ session('submission.key') }}">
                <p class="text-sm text-slate-600">شماره تأییدشده: <strong class="text-brand-800">{{ $mobile }}</strong></p>

                <label class="grid gap-1 text-sm font-medium" for="full_name">نام و نام خانوادگی خریدار
                    <input id="full_name" name="full_name" value="{{ old('full_name') }}" class="crm-input font-normal" required>
                </label>

                <label class="grid gap-1 text-sm font-medium" for="request_type_id">نوع درخواست
                    <select id="request_type_id" name="request_type_id" class="crm-input font-normal" required>
                        <option value="">انتخاب کنید</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(old('request_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </label>

                <fieldset class="grid gap-2">
                    <legend class="text-sm font-medium">تاریخ خرید</legend>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="grid gap-1 text-xs" for="purchase_day">روز
                            <input id="purchase_day" name="purchase_day" value="{{ old('purchase_day') }}" inputmode="numeric" class="crm-input" required>
                        </label>
                        <label class="grid gap-1 text-xs" for="purchase_month">ماه
                            <select id="purchase_month" name="purchase_month" class="crm-input" required>
                                @foreach ($months as $number => $name)
                                    <option value="{{ $number }}" @selected(old('purchase_month') == $number)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-1 text-xs" for="purchase_year">سال
                            <input id="purchase_year" name="purchase_year" value="{{ old('purchase_year', $year) }}" inputmode="numeric" class="crm-input" required>
                        </label>
                    </div>
                </fieldset>

                <label class="grid gap-1 text-sm font-medium" for="invoice_number">شماره فاکتور
                    <input id="invoice_number" name="invoice_number" value="{{ old('invoice_number') }}" class="crm-input font-normal">
                </label>
                <label class="grid gap-1 text-sm font-medium" for="product_name">نام یا مشخصات محصول
                    <input id="product_name" name="product_name" value="{{ old('product_name') }}" class="crm-input font-normal">
                </label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-1 text-sm font-medium" for="purchased_quantity">تعداد خریداری‌شده
                        <input id="purchased_quantity" name="purchased_quantity" value="{{ old('purchased_quantity') }}" inputmode="numeric" class="crm-input font-normal">
                    </label>
                    <label class="grid gap-1 text-sm font-medium" for="affected_quantity">تعداد اقلام معیوب، شکسته یا مغایر
                        <input id="affected_quantity" name="affected_quantity" value="{{ old('affected_quantity') }}" inputmode="numeric" class="crm-input font-normal">
                    </label>
                </div>
                <label class="grid gap-1 text-sm font-medium" for="description">شرح درخواست
                    <textarea id="description" name="description" rows="5" class="crm-input font-normal" required>{{ old('description') }}</textarea>
                </label>

                @foreach ($types as $type)
                    <div class="grid gap-3" data-type-fields="{{ $type->id }}" hidden>
                        @foreach ($type->activeFields as $field)
                            <label class="grid gap-1 text-sm font-medium">{{ $field->label }}
                                @if ($field->field_type->value === 'textarea')
                                    <textarea name="fields[{{ $field->field_key }}]" class="crm-input font-normal" @required($field->is_required)></textarea>
                                @elseif ($field->field_type->value === 'select')
                                    <select name="fields[{{ $field->field_key }}]" class="crm-input font-normal" @required($field->is_required)>
                                        <option value="">انتخاب کنید</option>
                                        @foreach ($field->options ?? [] as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input name="fields[{{ $field->field_key }}]" class="crm-input font-normal" @required($field->is_required)>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @endforeach

                <label class="grid gap-1 text-sm font-medium" for="attachments">مدارک و تصاویر
                    <input id="attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" class="text-sm">
                </label>
                <p class="text-xs leading-6 text-stone-500">{{ $company['required_documents_text'] }}</p>
                <button class="crm-btn-solid px-5 py-3">ثبت نهایی درخواست</button>
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

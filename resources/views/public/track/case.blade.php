@php($record = \App\Http\Controllers\TrackingController::customerPayload($case))
<section class="grid gap-4">
    <div class="flex flex-wrap items-start justify-between gap-3 rounded-2xl bg-white p-6 ring-1 ring-stone-200">
        <div>
            <p class="text-sm text-stone-500">کد رهگیری</p>
            <h1 class="mt-1 text-2xl font-bold tracking-wide text-stone-900" dir="ltr">{{ $record->tracking_code }}</h1>
            <p class="mt-3 text-sm text-stone-600">تاریخ ثبت: {{ \App\Support\JalaliDate::format($record->submitted_at) }}</p>
            <p class="text-sm text-stone-600">آخرین تغییر: {{ \App\Support\JalaliDate::format($record->updated_at) }}</p>
        </div>
        <div class="rounded-full bg-brand-50 px-4 py-2 text-sm font-medium text-brand-800">{{ $record->status->publicLabel() }}</div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <article class="rounded-2xl bg-white p-5 ring-1 ring-stone-200">
            <h2 class="font-semibold">خلاصه درخواست</h2>
            <dl class="mt-3 grid gap-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-stone-500">نوع</dt><dd>{{ $record->requestType->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-stone-500">تاریخ خرید</dt><dd>{{ \App\Support\JalaliDate::formatDate($record->purchase_date) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-stone-500">محصول</dt><dd>{{ $record->product_name ?: '—' }}</dd></div>
            </dl>
            <p class="mt-4 text-sm leading-7 text-stone-700">{{ $record->description }}</p>
        </article>
        <article class="rounded-2xl bg-white p-5 ring-1 ring-stone-200">
            <h2 class="font-semibold">پیام‌های رسمی</h2>
            <div class="mt-3 grid gap-3">
                @forelse ($record->messages as $message)
                    <p class="rounded-lg bg-stone-50 px-3 py-2 text-sm leading-7">{{ $message->body }}</p>
                @empty
                    <p class="text-sm text-stone-500">پیام قابل نمایشی ثبت نشده است.</p>
                @endforelse
            </div>
        </article>
    </div>

    @if ($record->status->value === 'waiting_customer')
        <form method="POST" action="{{ route('track.attachments.store') }}" enctype="multipart/form-data" class="rounded-2xl bg-white p-5 ring-1 ring-stone-200">
            @csrf
            <h2 class="font-semibold">ارسال مدرک تکمیلی</h2>
            <p class="mt-2 text-sm text-stone-600">{{ $record->customer_visible_summary }}</p>
            <input class="mt-3 block text-sm" type="file" name="attachments[]" multiple required accept=".jpg,.jpeg,.png,.webp,.pdf">
            <button class="mt-3 rounded-lg bg-brand-700 px-4 py-2 text-sm font-medium text-white">ارسال مدارک</button>
        </form>
    @endif

    <form method="POST" action="{{ route('track.logout') }}">
        @csrf
        <button class="text-sm text-stone-600 underline">خروج از پیگیری</button>
    </form>
</section>

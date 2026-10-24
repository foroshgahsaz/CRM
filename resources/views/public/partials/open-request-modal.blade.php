{{-- $trackingCode, $trackUrl --}}
<div class="crm-modal-backdrop fixed inset-0 z-50 flex items-center justify-center bg-slate-900/55 p-4" role="dialog" aria-modal="true" aria-labelledby="open-request-title">
    <div class="crm-card crm-card-glow w-full max-w-md p-6 sm:p-8">
        <h2 id="open-request-title" class="crm-page-title text-xl">درخواست باز دارید</h2>
        <p class="crm-page-lead mt-2 text-sm">
            برای شمارهٔ موبایل تأییدشده یک پرونده هنوز بسته نشده است. تا پایان رسیدگی، ثبت درخواست جدید ممکن نیست.
        </p>
        <p class="mt-4 rounded-xl bg-indigo-50 px-4 py-3 text-center text-sm text-brand-900">
            کد رهگیری:
            <strong dir="ltr" class="block text-lg tracking-widest text-brand-800">{{ $trackingCode }}</strong>
        </p>
        <div class="mt-6 flex flex-col gap-2 sm:flex-row">
            <a href="{{ $trackUrl }}" class="crm-btn-solid flex-1 text-center">رفتن به پیگیری</a>
            <a href="{{ route('requests.create') }}" class="crm-btn-outline flex-1 text-center">بستن</a>
        </div>
    </div>
</div>

{{-- $trackingCode, $trackUrl --}}
<div class="crm-modal-backdrop fixed inset-0 z-50 flex items-center justify-center bg-slate-900/55 p-4" role="dialog" aria-modal="true" aria-labelledby="open-request-title">
    <div class="hs-panel w-full max-w-md">
        <h2 id="open-request-title" class="hs-panel-title text-xl">درخواست باز دارید</h2>
        <p class="hs-panel-lead mt-2 text-sm">
            برای شمارهٔ موبایل تأییدشده یک پرونده هنوز بسته نشده است. تا پایان رسیدگی، ثبت درخواست جدید ممکن نیست.
        </p>
        <p class="mt-4 rounded-xl bg-[#e2ede6] px-4 py-3 text-center text-sm text-[#1e4d3e]">
            کد رهگیری:
            <strong dir="ltr" class="block text-lg tracking-widest text-brand-800">{{ $trackingCode }}</strong>
        </p>
        <div class="mt-6 flex flex-col gap-2 sm:flex-row">
            <a href="{{ $trackUrl }}" class="hs-btn hs-btn-primary flex-1 text-center">رفتن به پیگیری</a>
            <a href="{{ route('requests.create') }}" class="hs-btn hs-btn-outline flex-1 text-center">بستن</a>
        </div>
    </div>
</div>

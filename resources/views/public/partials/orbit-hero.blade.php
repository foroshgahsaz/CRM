<div class="hs-orbit-panel">
    <div class="hs-orbit-stage">
        <span class="hs-orbit-ring hs-orbit-ring-1"></span>
        <span class="hs-orbit-ring hs-orbit-ring-2"></span>
        <span class="hs-orbit-ring hs-orbit-ring-3"></span>
        <div class="hs-orbit-avatar-shell">
            <img
                src="{{ asset('images/hooman/support-avatar.webp') }}"
                alt="پشتیبانی {{ $company['company_name'] ?? 'هومن سرویس' }}"
                class="hs-orbit-avatar"
                width="160"
                height="160"
                loading="eager"
                decoding="async"
            >
        </div>
        <div class="hs-float-card hs-float-card--tr hidden sm:block">
            <strong>از ثبت درخواست تا پیگیری</strong>
            همه‌چیز در یک مسیر شفاف
        </div>
        <div class="hs-float-card hs-float-card--bl hidden sm:flex">
            <span class="hs-check" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M5 12.5 9.5 17 19 7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span>
                <strong>درخواست ثبت شد</strong>
                شروع یک پیگیری مطمئن
            </span>
        </div>
    </div>
</div>

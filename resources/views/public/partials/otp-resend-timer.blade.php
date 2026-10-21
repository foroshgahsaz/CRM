{{-- $remaining (seconds), $resendAction (route URL), $resendFields (hidden inputs) --}}
<div class="text-center" data-otp-timer data-remaining="{{ max(0, (int) $remaining) }}">
    <p class="py-1 text-sm font-medium text-amber-700" data-otp-countdown @if ($remaining <= 0) hidden @endif>
        ارسال مجدد تا <span dir="ltr" class="font-bold" data-otp-clock>--:--</span>
    </p>
    <form method="POST" action="{{ $resendAction }}" data-otp-resend @if ($remaining > 0) hidden @endif>
        @csrf
        @foreach ($resendFields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <button type="submit" class="crm-btn-outline mt-1 w-full py-2 text-sm">ارسال مجدد کد</button>
    </form>
</div>

@once
    <script>
        document.querySelectorAll('[data-otp-timer]').forEach((timer) => {
            let remaining = parseInt(timer.dataset.remaining || '0', 10);
            const countdown = timer.querySelector('[data-otp-countdown]');
            const clock = timer.querySelector('[data-otp-clock]');
            const resend = timer.querySelector('[data-otp-resend]');
            const render = () => {
                const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
                const seconds = String(remaining % 60).padStart(2, '0');
                clock.textContent = minutes + ':' + seconds;
                countdown.hidden = remaining <= 0;
                resend.hidden = remaining > 0;
            };
            render();
            if (remaining <= 0) {
                return;
            }
            const interval = setInterval(() => {
                remaining -= 1;
                render();
                if (remaining <= 0) {
                    clearInterval(interval);
                }
            }, 1000);
        });
    </script>
@endonce

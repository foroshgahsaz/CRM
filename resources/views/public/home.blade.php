@extends('public.layout')

@section('bare_main')
@endsection

@section('content')
    <section class="hs-hero">
        <div class="hs-container hs-hero-grid">
            <div>
                <span class="hs-label">خدمات پس از فروش</span>
                <h1 class="hs-hero-title mt-3">{{ $company['company_name'] }}</h1>
                <p class="hs-hero-lead">{{ $company['company_description'] ?: $company['company_tagline'] }}</p>
                <div class="hs-hero-actions">
                    <a href="{{ route('requests.create') }}" class="hs-btn hs-btn-primary">ثبت درخواست</a>
                    <a href="{{ route('track.show') }}" class="hs-btn hs-btn-outline">مراحل پیگیری</a>
                </div>
            </div>
            <div>
                @include('public.partials.orbit-hero')
            </div>
        </div>
    </section>

    <section class="hs-section" id="services">
        <div class="hs-container">
            <div class="hs-section-head">
                <h2 class="hs-section-title">چطور می‌تونیم کمکتون کنیم؟</h2>
                <p class="hs-section-sub">نوع درخواست خود را انتخاب کنید؛ تیم ما در کنار شماست.</p>
            </div>
            <div class="hs-service-grid">
                @forelse ($types as $index => $type)
                    <article class="hs-service-card">
                        <div class="hs-service-icon">{{ $index + 1 }}</div>
                        <h3>{{ $type->name }}</h3>
                        <p>{{ $type->description ?: 'ثبت و پیگیری درخواست مربوط به '.$type->name.' از همین سامانه.' }}</p>
                    </article>
                @empty
                    @foreach (['ثبت درخواست', 'پیگیری وضعیت', 'ارسال مدارک'] as $index => $title)
                        <article class="hs-service-card">
                            <div class="hs-service-icon">{{ $index + 1 }}</div>
                            <h3>{{ $title }}</h3>
                            <p>مسیر ساده و امن برای مشتریان.</p>
                        </article>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

    <section class="hs-process-band" id="process">
        <div class="hs-container">
            <div class="hs-section-head">
                <h2 class="hs-section-title">فرآیند ساده در چند گام</h2>
                <p class="hs-section-sub">از ثبت درخواست تا پاسخ — شفاف و قابل پیگیری</p>
            </div>
            <div class="hs-process-steps">
                @foreach ([
                    ['۱', 'تأیید موبایل', 'دریافت رمز یک‌بارمصرف'],
                    ['۲', 'تکمیل فرم', 'اطلاعات خرید و مدارک'],
                    ['۳', 'بررسی کارشناس', 'ارجاع و رسیدگی تخصصی'],
                    ['۴', 'نتیجه و پیگیری', 'کد رهگیری همیشه در دسترس شما'],
                ] as [$num, $title, $text])
                    <div class="hs-process-step">
                        <div class="hs-process-dot"></div>
                        <span>گام {{ $num }}</span>
                        <h4>{{ $title }}</h4>
                        <p>{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="hs-section" id="register">
        <div class="hs-container hs-register-grid">
            <div class="hs-contact-card">
                <div>
                    <h3>حرفه‌ای گوش می‌دهیم</h3>
                    <p>{{ $company['process_guide'] ?: 'تیم پشتیبانی ما درخواست شما را با دقت بررسی می‌کند و در هر مرحله در کنار شماست.' }}</p>
                </div>
                <div class="hs-contact-meta">
                    @if ($company['contact_phone'])
                        <p>تلفن: {{ $company['contact_phone'] }}</p>
                    @endif
                    @if ($company['contact_email'])
                        <p>ایمیل: {{ $company['contact_email'] }}</p>
                    @endif
                    @if ($company['contact_address'])
                        <p>{{ $company['contact_address'] }}</p>
                    @endif
                </div>
            </div>

            <div class="hs-form-card">
                <h3>ثبت درخواست سریع</h3>
                <p class="hs-form-note">ابتدا موبایل را تأیید کنید؛ بقیهٔ فرم در مرحله بعد تکمیل می‌شود.</p>

                <form method="POST" action="{{ route('requests.otp.send') }}" class="mt-2">
                    @csrf
                    <div class="hs-field">
                        <label for="home_mobile">شماره موبایل</label>
                        <input id="home_mobile" name="mobile" type="tel" dir="ltr" inputmode="numeric" class="hs-input" placeholder="09123456789" required autocomplete="tel">
                    </div>
                    <div class="hs-field hs-field-decor" aria-hidden="true">
                        <label>نام و نام خانوادگی</label>
                        <input type="text" class="hs-input" placeholder="در مرحله بعد" disabled tabindex="-1">
                    </div>
                    <div class="hs-field hs-field-decor" aria-hidden="true">
                        <label>نوع درخواست</label>
                        <select class="hs-select" disabled tabindex="-1">
                            <option>پس از تأیید موبایل</option>
                        </select>
                    </div>
                    <button type="submit" class="hs-btn hs-btn-primary">ثبت درخواست — دریافت کد</button>
                </form>
            </div>
        </div>
    </section>

    <section class="hs-section" id="faq">
        <div class="hs-container">
            <div class="hs-section-head">
                <h2 class="hs-section-title">سوالات متداول</h2>
            </div>
            <div class="hs-faq">
                <details class="hs-faq-item">
                    <summary>چطور درخواست ثبت کنم؟</summary>
                    <p>روی «ثبت درخواست» بزنید، موبایل را با رمز یک‌بارمصرف تأیید کنید، سپس فرم و مدارک را تکمیل کنید. کد رهگیری به شما داده می‌شود.</p>
                </details>
                <details class="hs-faq-item">
                    <summary>مدارک لازم چیست؟</summary>
                    <p>{{ $company['required_documents_text'] ?: 'حداقل یک فایل تصویر یا PDF از فاکتور یا کالا در فرم ثبت الزامی است.' }}</p>
                </details>
                <details class="hs-faq-item">
                    <summary>آیا می‌توانم چند درخواست همزمان باز داشته باشم؟</summary>
                    <p>خیر. تا بسته شدن پروندهٔ قبلی، ثبت درخواست جدید برای همان موبایل ممکن نیست؛ کد رهگیری پروندهٔ باز را به شما نشان می‌دهیم.</p>
                </details>
                <details class="hs-faq-item">
                    <summary>پیامک OTP چیست؟</summary>
                    <p>برای امنیت، ورود و ثبت با رمز یک‌بارمصرف انجام می‌شود. جزئیات در صفحه <a href="{{ route('site.privacy') }}" class="text-[#004d40] font-semibold">حریم خصوصی</a>.</p>
                </details>
            </div>
        </div>
    </section>
@endsection

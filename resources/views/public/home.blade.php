@extends('public.layout-chinibazar')

@section('content')
@php
    $logoUrl = asset('images/hooman/logo.jpg');
    $avatarUrl = asset('images/hooman/support-avatar.webp');
    $tagline = $company['company_tagline'] ?: 'CARE · AFTER SALES';
@endphp
<main class="app" id="top">
    <div class="wrap">
        <header class="topbar">
            <a class="brand" href="#top">
                <span class="logo"><img src="{{ $logoUrl }}" alt="{{ $company['company_name'] }}"></span>
                <span>{{ $company['company_name'] }}<small>{{ $tagline }}</small></span>
            </a>
            <nav class="nav">
                <a href="#services">خدمات ما</a>
                <a href="{{ route('track.show') }}">پیگیری درخواست</a>
                <a href="#request">ثبت درخواست</a>
                <a href="#faq">سوالات متداول</a>
            </nav>
            <a class="top-cta" href="#request">ثبت درخواست <span>↙</span></a>
        </header>

        <section class="hero">
            <div>
                <div class="eyebrow"><span class="dot"></span> همراه شما، حتی بعد از خرید</div>
                <h1>تجربه‌ی خوب خرید،<br><span>اینجا تمام نمی‌شود.</span></h1>
                <p class="intro">برای پیگیری سفارش، درخواست خدمات یا دریافت راهنمایی درباره محصولات، کنار شما هستیم. درخواستت رو ثبت کن؛ ادامه مسیر رو با هم پیش می‌بریم.</p>
                <div class="hero-actions">
                    <a class="btn" href="#request">ثبت درخواست خدمات <span>↙</span></a>
                    <a class="btn secondary" href="{{ route('track.show') }}">پیگیری درخواست</a>
                </div>
                <div class="trust">
                    <span>✓ پیگیری شفاف درخواست</span>
                    <span>✓ راهنمایی تخصصی محصول</span>
                    <span>✓ ارتباط آسان</span>
                </div>
            </div>
            <div class="visual" aria-label="پشتیبانی آنلاین">
                <div class="plate">
                    <div class="plate-inner">
                        <img src="{{ $avatarUrl }}" alt="اپراتور پشتیبانی" width="140" height="140">
                    </div>
                </div>
                <div class="float-card fc1">
                    <strong>همراه شما هستیم</strong>
                    <span>از ثبت درخواست تا پیگیری</span>
                </div>
                <div class="float-card fc2">
                    <div class="mini-icon">✓</div>
                    <div>
                        <strong>درخواست شما</strong>
                        <span>شروع یک پیگیری ساده</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="services">
            <div class="section-head">
                <div>
                    <div class="kicker">HOW CAN WE HELP?</div>
                    <h2>چطور می‌تونیم کمکت کنیم؟</h2>
                </div>
                <p class="muted">موضوع درخواستت رو انتخاب کن تا راحت‌تر به مسیر درست راهنمایی بشی.</p>
            </div>
            <div class="services">
                @php($serviceIcons = ['♧', '✳', '↗'])
                @forelse ($types->take(3) as $index => $type)
                    <article class="service">
                        <div class="mini-icon">{{ $serviceIcons[$index] ?? '✳' }}</div>
                        <h3>{{ $type->name }}</h3>
                        <p>{{ $type->description ?: 'ثبت و پیگیری «'.$type->name.'» از همین سامانه.' }}</p>
                    </article>
                @empty
                    <article class="service">
                        <div class="mini-icon">♧</div>
                        <h3>ثبت درخواست</h3>
                        <p>نوع درخواست را در فرم پایین صفحه انتخاب کنید.</p>
                    </article>
                    <article class="service">
                        <div class="mini-icon">✳</div>
                        <h3>پیگیری وضعیت</h3>
                        <p>با کد رهگیری از بخش «پیگیری درخواست» پرونده را ببینید.</p>
                    </article>
                    <article class="service">
                        <div class="mini-icon">↗</div>
                        <h3>پشتیبانی</h3>
                        <p>تیم ما درخواست شما را بررسی و پاسخ می‌دهد.</p>
                    </article>
                @endforelse
            </div>
        </section>
    </div>

    <section class="process" id="process">
        <div class="wrap">
            <div class="kicker">A SIMPLE PROCESS</div>
            <h2>از درخواست تا پاسخ، در ۴ قدم</h2>
            <p class="muted">فرآیند را ساده و شفاف طراحی کردیم تا بدانید قدم بعدی چیست.</p>
            <div class="steps">
                <div class="step">
                    <div class="num">مرحله ۰۱</div>
                    <h3>ثبت درخواست</h3>
                    <p>فرم کوتاه را با اطلاعات تماس و موضوع درخواست تکمیل کن.</p>
                </div>
                <div class="step">
                    <div class="num">مرحله ۰۲</div>
                    <h3>بررسی اطلاعات</h3>
                    <p>جزئیات درخواست برای بررسی اولیه آماده می‌شود.</p>
                </div>
                <div class="step">
                    <div class="num">مرحله ۰۳</div>
                    <h3>تماس و هماهنگی</h3>
                    <p>برای پیگیری و مشخص شدن راه‌حل با شما هماهنگ می‌کنیم.</p>
                </div>
                <div class="step">
                    <div class="num">مرحله ۰۴</div>
                    <h3>پیگیری تا نتیجه</h3>
                    <p>وضعیت درخواست از طریق مسیر ارتباطی هماهنگ‌شده دنبال می‌شود.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="wrap">
        <section class="section request" id="request">
            <aside class="request-aside">
                <div class="eyebrow" style="background:#ffffff20;color:white">پشتیبانی {{ $company['company_name'] }}</div>
                <h2>ما برای شنیدن حرفت اینجاییم.</h2>
                <p>چند خط درباره درخواستت بنویس. اطلاعاتت کمک می‌کنه سریع‌تر موضوع رو بررسی کنیم.</p>
                <div class="contact-line">
                    @if ($company['contact_phone'])
                        تماس: {{ $company['contact_phone'] }}<br>
                    @endif
                    پاسخ‌گویی به درخواست‌ها<br>
                    در روزها و ساعات کاری مجموعه
                </div>
            </aside>
            <div class="form-card">
                <h3>ثبت درخواست خدمات</h3>
                <p>نوع درخواست را از لیست فعال سامانه انتخاب کنید؛ پس از تأیید موبایل، همان فرم داینامیک (فیلدهای اختصاصی نوع درخواست) را تکمیل می‌کنید.</p>

                @if (session('status'))
                    <div class="cb-flash cb-flash-success" role="status">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="cb-flash cb-flash-error" role="alert">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="cb-flash cb-flash-error" role="alert">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('requests.otp.send') }}" id="serviceForm">
                    @csrf
                    <input type="hidden" name="source" value="home">
                    <div class="fields">
                        <label class="field">نام و نام خانوادگی
                            <input name="full_name" placeholder="نام شما" value="{{ old('full_name', $prefill['full_name'] ?? '') }}" autocomplete="name">
                        </label>
                        <label class="field">شماره تماس *
                            <input required name="mobile" type="tel" dir="ltr" inputmode="tel" placeholder="09121234567" value="{{ old('mobile') }}" autocomplete="tel">
                        </label>
                        <label class="field">نوع درخواست *
                            <select name="request_type_id" id="home_request_type_id" required>
                                <option value="">انتخاب نوع درخواست</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected(old('request_type_id', $prefill['request_type_id'] ?? '') == $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="field">شماره فاکتور (اختیاری)
                            <input name="invoice_number" placeholder="مثلاً CB-1024" value="{{ old('invoice_number', $prefill['invoice_number'] ?? '') }}">
                        </label>
                        @include('public.partials.request-type-fields-groups', [
                            'types' => $types,
                            'variant' => 'premium',
                            'prefill' => $prefill,
                        ])
                        <label class="field full">شرح درخواست
                            <textarea name="description" placeholder="لطفاً موضوع را با جزئیات کوتاه توضیح دهید...">{{ old('description', $prefill['description'] ?? '') }}</textarea>
                        </label>
                    </div>
                    <button class="btn form-submit" type="submit">ادامه — دریافت کد تأیید <span>↙</span></button>
                    <div class="notice">پس از تأیید موبایل، به <a href="{{ route('requests.create') }}">فرم ثبت درخواست</a> با همان نوع و فیلدهای داینامیک هدایت می‌شوید. پیگیری با <a href="{{ route('track.show') }}">کد رهگیری</a>.</div>
                </form>
                @include('public.partials.request-type-fields-sync', ['selectId' => 'home_request_type_id'])
            </div>
        </section>

        <section class="section" id="faq">
            <div class="faq">
                <div class="kicker">FAQ</div>
                <h2>سوالات پرتکرار</h2>
                <details>
                    <summary>چطور درخواست خدمات ثبت کنم؟</summary>
                    <p>فرم ثبت درخواست را تکمیل کن و موضوع و شرح مشکل را بنویس. برای پیگیری سفارش، شماره سفارش هم کمک‌کننده است.</p>
                </details>
                <details>
                    <summary>برای پیگیری سفارش چه اطلاعاتی لازم است؟</summary>
                    <p>شماره تماس ثبت‌شده هنگام خرید و در صورت دسترسی، شماره سفارش را آماده داشته باش.</p>
                </details>
                <details>
                    <summary>بعد از ثبت درخواست چه اتفاقی می‌افتد؟</summary>
                    <p>پس از تأیید موبایل، درخواست در سامانه ثبت می‌شود و تیم مربوطه طبق فرآیند مجموعه آن را بررسی و پیگیری می‌کند.</p>
                </details>
            </div>
        </section>

        <footer>
            <div class="foot">
                <span>© {{ $company['company_name'] }} — خدمات پس از فروش</span>
                <span>
                    <a href="{{ route('track.show') }}">پیگیری درخواست ↗</a>
                    　·　
                    <a href="{{ route('site.contact') }}">تماس با ما</a>
                    　·　
                    تجربه‌ای بهتر، حتی بعد از خرید
                </span>
            </div>
        </footer>
    </div>
</main>
<a class="floating-help" href="#request">✳ ثبت درخواست</a>
@endsection

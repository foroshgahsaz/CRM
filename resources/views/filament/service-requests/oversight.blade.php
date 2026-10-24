@php
    use App\Enums\ApprovalDecisionType;
    use App\Filament\Support\RequestTimeline;
    use App\Support\JalaliDate;

    $request = RequestTimeline::load($getRecord());
    $decisions = $request->approvalDecisions->sortByDesc('id')->values();
    $views = $request->views->sortByDesc('last_viewed_at')->values();
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
            <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5 text-indigo-500" />
            تصمیم‌های تأیید نهایی
        </h3>
        @forelse ($decisions as $decision)
            @php($approved = $decision->decision === ApprovalDecisionType::Approved)
            <div class="mt-4 border-s-4 ps-3 {{ $approved ? 'border-emerald-500' : 'border-rose-500' }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span @class(['rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset', RequestTimeline::tone($approved ? 'success' : 'danger')])>{{ $decision->decision->label() }}</span>
                    <span class="text-xs text-slate-500"><span dir="ltr">{{ JalaliDate::format($decision->created_at) }}</span> • {{ $decision->decider?->name ?? '—' }}</span>
                </div>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{{ $decision->reason }}</p>
            </div>
        @empty
            <p class="mt-4 text-sm text-slate-500">هنوز تصمیمی ثبت نشده است.</p>
        @endforelse
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-950/5 dark:bg-gray-900 dark:ring-white/10">
        <h3 class="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
            <x-filament::icon icon="heroicon-o-eye" class="h-5 w-5 text-indigo-500" />
            چه کسانی پرونده را دیده‌اند
        </h3>
        @forelse ($views as $view)
            <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ mb_substr($view->user?->name ?? '?', 0, 1) }}</span>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">{{ $view->user?->name ?? '—' }}</p>
                        <p class="text-xs text-slate-500">{{ $view->kind === 'assignee' ? 'مسئول مأموریت' : 'کارشناس یا ناظر' }}</p>
                    </div>
                </div>
                <div class="text-end text-xs text-slate-500">
                    <p>{{ $view->view_count }} بار</p>
                    <p dir="ltr">{{ JalaliDate::format($view->last_viewed_at ?? $view->first_viewed_at) }}</p>
                </div>
            </div>
        @empty
            <p class="mt-4 text-sm text-slate-500">هنوز کسی پرونده را ندیده است.</p>
        @endforelse
    </section>
</div>

@php
    use App\Filament\Support\RequestTimeline;

    $h = RequestTimeline::header($getRecord());
@endphp

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="bg-gradient-to-l from-indigo-600 via-violet-600 to-fuchsia-600 px-6 py-5 text-white">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-medium text-white/70">کد رهگیری</p>
                <p class="mt-1 font-mono text-2xl font-bold tracking-wider" dir="ltr">{{ $h['tracking'] }}</p>
                <p class="mt-2 text-sm text-white/85">
                    {{ $h['type'] ?? '—' }}
                    @if ($h['product'])
                        <span class="mx-1 text-white/50">•</span>{{ $h['product'] }}
                    @endif
                    @if ($h['customer'])
                        <span class="mx-1 text-white/50">•</span>{{ $h['customer'] }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-sm font-semibold ring-1 ring-white/30 backdrop-blur">
                    <span class="h-2 w-2 rounded-full bg-white"></span>
                    {{ $h['status'] }}
                </span>
                <span @class([
                    'inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ring-1',
                    'bg-rose-500/90 ring-rose-200/50' => $h['priorityColor'] === 'danger',
                    'bg-amber-400/90 text-amber-950 ring-amber-100/50' => $h['priorityColor'] === 'warning',
                    'bg-white/15 ring-white/30' => ! in_array($h['priorityColor'], ['danger', 'warning'], true),
                ])>
                    اولویت {{ $h['priority'] }}
                </span>
                @if ($h['overdue'])
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-600 px-3 py-1 text-sm font-semibold ring-1 ring-rose-200/50">
                        <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-4 w-4" />
                        معوق
                    </span>
                @endif
            </div>
        </div>
    </div>

    <dl class="grid grid-cols-2 gap-px bg-slate-100 text-sm md:grid-cols-4 dark:bg-white/5">
        @foreach ([
            ['heroicon-o-user-circle', 'مسئول فعلی', $h['owner'] ?? 'بدون مسئول', null],
            ['heroicon-o-building-office-2', 'واحد', $h['unit'] ?? '—', null],
            ['heroicon-o-calendar-days', 'ثبت', $h['submitted'], $h['submittedAgo']],
            ['heroicon-o-clock', 'سررسید', $h['due'] ?? 'تعیین نشده', $h['dueHint']],
        ] as [$icon, $label, $value, $hint])
            <div class="flex items-start gap-3 bg-white px-5 py-4 dark:bg-gray-900">
                <x-filament::icon :icon="$icon" class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" />
                <div class="min-w-0">
                    <dt class="text-xs text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                    <dd class="mt-0.5 truncate font-semibold text-slate-900 dark:text-white">{{ $value }}</dd>
                    @if ($hint)
                        <dd @class([
                            'mt-0.5 text-xs',
                            'font-semibold text-rose-600 dark:text-rose-400' => $label === 'سررسید' && $h['overdue'],
                            'text-slate-500 dark:text-slate-400' => ! ($label === 'سررسید' && $h['overdue']),
                        ])>{{ $hint }}</dd>
                    @endif
                </div>
            </div>
        @endforeach
    </dl>

    <ol class="flex items-center gap-2 overflow-x-auto px-6 py-5">
        @foreach ($h['steps'] as $index => $step)
            <li class="flex min-w-max flex-1 items-center gap-2">
                <span @class([
                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ring-4',
                    'bg-emerald-500 text-white ring-emerald-100 dark:ring-emerald-500/20' => $step['state'] === 'done',
                    'bg-indigo-600 text-white ring-indigo-100 dark:ring-indigo-500/30' => $step['state'] === 'current',
                    'bg-slate-100 text-slate-400 ring-slate-50 dark:bg-white/5 dark:ring-transparent' => $step['state'] === 'upcoming',
                ])>
                    @if ($step['state'] === 'done')
                        <x-filament::icon icon="heroicon-m-check" class="h-4 w-4" />
                    @else
                        {{ $index + 1 }}
                    @endif
                </span>
                <span @class([
                    'text-sm',
                    'font-semibold text-slate-900 dark:text-white' => $step['state'] !== 'upcoming',
                    'text-slate-400' => $step['state'] === 'upcoming',
                ])>{{ $step['label'] }}</span>
                @unless ($loop->last)
                    <span @class([
                        'mx-1 h-0.5 min-w-6 flex-1 rounded-full',
                        'bg-emerald-400' => $step['state'] === 'done',
                        'bg-slate-200 dark:bg-white/10' => $step['state'] !== 'done',
                    ])></span>
                @endunless
            </li>
        @endforeach
    </ol>
</div>

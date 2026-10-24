@php
    use App\Enums\AssignmentStatus;
    use App\Filament\Support\RequestTimeline;
    use App\Support\JalaliDate;

    $assignments = RequestTimeline::load($getRecord())->assignments->sortByDesc('id')->values();
@endphp

@if ($assignments->isEmpty())
    <p class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-white/10">هنوز ارجاعی ثبت نشده است.</p>
@else
    <ol class="relative space-y-4">
        @foreach ($assignments as $assignment)
            @php
                $active = $assignment->status === AssignmentStatus::Active;
                $color = match ($assignment->status) {
                    AssignmentStatus::Active => 'primary',
                    AssignmentStatus::Completed => 'success',
                    AssignmentStatus::Cancelled => 'danger',
                    default => 'gray',
                };
                $from = $assignment->fromUser?->name ?? $assignment->fromUnit?->name ?? 'سامانه';
                $to = $assignment->toUser?->name ?? ($assignment->toUnit ? 'واحد '.$assignment->toUnit->name : '—');
            @endphp
            <li @class([
                'rounded-2xl bg-white p-5 shadow-sm ring-1 dark:bg-gray-900',
                'ring-2 ring-indigo-500/60' => $active,
                'ring-slate-950/5 dark:ring-white/10' => ! $active,
            ])>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-medium text-slate-700 dark:bg-white/5 dark:text-slate-300">{{ $from }}</span>
                        <x-filament::icon icon="heroicon-m-arrow-left" class="h-4 w-4 text-slate-400" />
                        <span class="rounded-lg bg-indigo-50 px-2.5 py-1 font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">{{ $to }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">{{ $assignment->assignment_type->label() }}</span>
                        <span @class(['rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset', RequestTimeline::tone($color)])>
                            {{ $assignment->status->label() }}
                        </span>
                    </div>
                </div>

                @if ($assignment->reason || $assignment->instructions)
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        @if ($assignment->reason)
                            <div class="rounded-xl bg-slate-50 p-3 text-sm dark:bg-white/5">
                                <p class="text-xs font-semibold text-slate-500">دلیل ارجاع</p>
                                <p class="mt-1 whitespace-pre-line text-slate-800 dark:text-slate-200">{{ $assignment->reason }}</p>
                            </div>
                        @endif
                        @if ($assignment->instructions)
                            <div class="rounded-xl bg-slate-50 p-3 text-sm dark:bg-white/5">
                                <p class="text-xs font-semibold text-slate-500">دستور کار</p>
                                <p class="mt-1 whitespace-pre-line text-slate-800 dark:text-slate-200">{{ $assignment->instructions }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                <dl class="mt-4 grid grid-cols-2 gap-3 text-xs md:grid-cols-4">
                    @foreach ([
                        'ارجاع' => $assignment->created_at,
                        'اولین مشاهده' => $assignment->first_viewed_at,
                        'سررسید' => $assignment->due_at,
                        'پایان' => $assignment->completed_at,
                    ] as $label => $at)
                        <div>
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200" dir="ltr">{{ $at ? JalaliDate::format($at) : '—' }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($assignment->completion_summary)
                    <div class="mt-4 rounded-xl border-s-4 border-emerald-500 bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
                        <p class="text-xs font-semibold">نتیجهٔ کارشناسی</p>
                        <p class="mt-1 whitespace-pre-line">{{ $assignment->completion_summary }}</p>
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
@endif

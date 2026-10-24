@php
    use App\Filament\Support\RequestTimeline;
    use App\Support\JalaliDate;

    $allocations = RequestTimeline::load($getRecord())->inventoryAllocations->sortByDesc('id')->values();
@endphp

<div class="grid gap-4 md:grid-cols-2">
    @foreach ($allocations as $allocation)
        @php($active = $allocation->cancelled_at === null)
        <div @class([
            'rounded-2xl bg-white p-4 shadow-sm ring-1 dark:bg-gray-900',
            'ring-emerald-300 dark:ring-emerald-500/30' => $active,
            'opacity-70 ring-slate-950/5 dark:ring-white/10' => ! $active,
        ])>
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <x-filament::icon icon="heroicon-o-cube" class="h-6 w-6" />
                    </span>
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $allocation->product_name_snapshot }}</p>
                        <p class="mt-0.5 font-mono text-xs text-slate-500" dir="ltr">{{ $allocation->product_code_snapshot }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $allocation->warehouse_name_snapshot }} • ۱ عدد</p>
                    </div>
                </div>
                <span @class(['rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset', RequestTimeline::tone($active ? 'success' : 'gray')])>
                    {{ $active ? 'فعال' : 'لغو شده' }}
                </span>
            </div>
            <p class="mt-3 text-xs text-slate-500">
                ارجاع توسط <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $allocation->allocator?->name ?? '—' }}</span>
                • <span dir="ltr">{{ JalaliDate::format($allocation->created_at) }}</span>
            </p>
            @if ($allocation->note)
                <p class="mt-2 rounded-lg bg-slate-50 p-2 text-sm text-slate-700 dark:bg-white/5 dark:text-slate-300">{{ $allocation->note }}</p>
            @endif
            @unless ($active)
                <p class="mt-2 text-xs text-rose-600 dark:text-rose-400">
                    لغو توسط {{ $allocation->canceller?->name ?? '—' }} در <span dir="ltr">{{ JalaliDate::format($allocation->cancelled_at) }}</span>: {{ $allocation->cancel_reason }}
                </p>
            @endunless
        </div>
    @endforeach
</div>

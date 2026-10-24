@php
    use App\Filament\Support\RequestTimeline;

    $groups = RequestTimeline::grouped($getRecord(), auth()->user());
    $filters = [
        'all' => 'همه',
        'status' => 'وضعیت',
        'assignment' => 'ارجاع',
        'message' => 'پیام',
        'attachment' => 'مدرک',
        'approval' => 'تأیید',
        'inventory' => 'انبار',
    ];
@endphp

<div x-data="{ filter: 'all' }" class="space-y-6">
    <div class="flex flex-wrap gap-2">
        @foreach ($filters as $key => $label)
            <button
                type="button"
                x-on:click="filter = @js($key)"
                x-bind:class="filter === @js($key)
                    ? 'bg-indigo-600 text-white ring-indigo-600 shadow-sm'
                    : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50 dark:bg-white/5 dark:text-slate-300 dark:ring-white/10'"
                class="rounded-full px-3 py-1 text-xs font-semibold ring-1 transition"
            >{{ $label }}</button>
        @endforeach
    </div>

    @forelse ($groups as $group)
        @php
            $kinds = collect($group['events'])->map(fn (array $event): string => RequestTimeline::filterKey($event['kind']))->unique()->values()->all();
        @endphp
        <section x-show="filter === 'all' || @js($kinds).includes(filter)">
            <div class="mb-3 flex items-center gap-3">
                <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white dark:bg-white/10">{{ $group['day'] }}</span>
                <span class="h-px flex-1 bg-slate-200 dark:bg-white/10"></span>
            </div>

            <ol class="relative ms-4 border-s-2 border-slate-200 dark:border-white/10">
                @foreach ($group['events'] as $event)
                    <li
                        x-show="filter === 'all' || filter === @js(RequestTimeline::filterKey($event['kind']))"
                        x-transition.opacity
                        class="relative mb-4 ps-8 last:mb-0"
                    >
                        <span @class([
                            'absolute -start-[17px] top-1 flex h-8 w-8 items-center justify-center rounded-full ring-4',
                            RequestTimeline::tone($event['color'], 'dot'),
                        ])>
                            <x-filament::icon :icon="$event['icon']" class="h-4 w-4" />
                        </span>

                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-950/5 transition hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $event['title'] }}</p>
                                    @if ($event['actor'])
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $event['actor'] }}</p>
                                    @endif
                                </div>
                                <time class="shrink-0 text-xs text-slate-500 dark:text-slate-400" title="{{ $event['ago'] }}" dir="ltr">{{ $event['time'] }}</time>
                            </div>

                            @if ($event['body'])
                                <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($event['body'], 600) }}</p>
                            @endif

                            @if ($event['chips'] || $event['url'])
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    @foreach ($event['chips'] as $chip)
                                        <span @class(['rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset', RequestTimeline::tone($event['color'])])>{{ $chip }}</span>
                                    @endforeach
                                    @if ($event['url'])
                                        <a href="{{ $event['url'] }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                                            <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-4 w-4" />
                                            دانلود
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @empty
        <p class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-white/10">هنوز رویدادی ثبت نشده است.</p>
    @endforelse
</div>

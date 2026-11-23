<x-filament-panels::page>
    <div class="space-y-6 text-sm leading-7">
        <x-filament::section>
            <x-slot name="heading">زمان‌بندی خودکار</x-slot>
            <p>
                هر شب ساعت <strong>{{ config('crm.backup.schedule_time', '20:00') }}</strong>
                ({{ config('app.timezone') }}) بکاپ خودکار با برچسب <code>auto</code> ساخته می‌شود.
            </p>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                نگه‌داری: حداکثر <strong>{{ config('crm.backup.retention', 2) }}</strong> فایل آخر روی دیسک
                <code class="text-xs">{{ config('crm.backup.root') }}</code>
            </p>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                برای اجرای خودکار، cron سرور باید <code>php artisan schedule:run</code> را هر دقیقه صدا بزند.
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">نسخه‌های موجود</x-slot>

            @if ($backups === [])
                <p class="text-gray-600 dark:text-gray-400">هنوز بکاپی ثبت نشده است.</p>
            @else
                <ul class="divide-y divide-gray-200 dark:divide-white/10 rounded-xl border border-gray-200 dark:border-white/10">
                    @foreach ($backups as $item)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                            <div>
                                <div class="font-semibold">{{ $item['filename'] }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $item['created_at'] }}
                                    · {{ number_format($item['bytes'] / 1024 / 1024, 2) }} مگابایت
                                </div>
                            </div>
                            <x-filament::button
                                tag="a"
                                href="{{ route('staff.backups.download', ['filename' => $item['filename']]) }}"
                                size="sm"
                                icon="heroicon-o-arrow-down-tray"
                            >
                                دانلود
                            </x-filament::button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
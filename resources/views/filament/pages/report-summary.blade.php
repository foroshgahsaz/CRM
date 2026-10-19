<div class="space-y-4">
    @if (! empty($report['error']))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $report['error'] }}</div>
    @else
        <p class="text-sm text-gray-600">زمان رسیدگی از تاریخ ثبت تا زمان بستن پرونده است. زمان کارشناسی از شروع کار مسئول تا تکمیل همان مأموریت است.</p>
        <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                'submitted_count' => 'ثبت‌شده',
                'unassigned_count' => 'تخصیص‌نیافته',
                'overdue_count' => 'معوق',
                'closed_count' => 'بسته‌شده',
                'rejection_count' => 'بازگشت برای اصلاح',
                'avg_assignments' => 'میانگین ارجاع',
                'avg_hours_to_first_view' => 'ساعت تا اولین مشاهده',
                'avg_hours_to_start' => 'ساعت تا شروع',
                'avg_expert_hours' => 'ساعت کارشناسی',
                'avg_hours_to_approval' => 'ساعت تا تأیید',
                'avg_handling_hours' => 'ساعت رسیدگی تا بستن',
            ] as $key => $label)
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200">
                    <dt class="text-sm text-gray-500">{{ $label }}</dt>
                    <dd class="mt-1 text-2xl font-semibold">{{ $report[$key] ?? '—' }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</div>

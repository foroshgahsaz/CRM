@php
    use App\Enums\MessageVisibility;
    use App\Filament\Support\RequestTimeline;
    use App\Support\JalaliDate;

    $messages = RequestTimeline::visibleMessages($getRecord(), auth()->user());
@endphp

@if ($messages->isEmpty())
    <p class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-white/10">پیامی ثبت نشده است. از دکمهٔ «پیام‌ها» در بالای صفحه می‌توانید پیام یا یادداشت ثبت کنید.</p>
@else
    <div class="space-y-4">
        @foreach ($messages as $message)
            @php
                $fromCustomer = $message->sender_id === null;
                $mine = $message->sender_id !== null && $message->sender_id === auth()->id();
                $bubble = match ($message->visibility) {
                    MessageVisibility::AdminOnly => 'bg-amber-50 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-500/20',
                    MessageVisibility::CustomerVisible => 'bg-indigo-50 ring-indigo-200 dark:bg-indigo-500/10 dark:ring-indigo-500/20',
                    default => 'bg-white ring-slate-200 dark:bg-gray-900 dark:ring-white/10',
                };
                $chip = match ($message->visibility) {
                    MessageVisibility::AdminOnly => 'warning',
                    MessageVisibility::CustomerVisible => 'primary',
                    default => 'gray',
                };
                $name = $message->sender?->name ?? $message->customer?->full_name ?? 'مشتری';
            @endphp
            <div @class(['flex gap-3', 'flex-row-reverse' => $mine])>
                <span @class([
                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white',
                    'bg-fuchsia-500' => $fromCustomer,
                    'bg-indigo-600' => ! $fromCustomer,
                ])>{{ mb_substr($name, 0, 1) }}</span>

                <div @class(['max-w-3xl flex-1 rounded-2xl p-4 shadow-sm ring-1', $bubble, 'rounded-ss-sm' => ! $mine, 'rounded-se-sm' => $mine])>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $name }}</span>
                            <span class="text-xs text-slate-500">{{ $message->message_type->label() }}</span>
                            <span @class(['rounded-md px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset', RequestTimeline::tone($chip)])>{{ $message->visibility->label() }}</span>
                        </div>
                        <time class="text-xs text-slate-500" dir="ltr">{{ JalaliDate::format($message->created_at) }}</time>
                    </div>
                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-800 dark:text-slate-200">{{ $message->body }}</p>
                </div>
            </div>
        @endforeach
    </div>
@endif

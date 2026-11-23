@php
    use App\Enums\AttachmentVisibility;
    use App\Filament\Support\RequestTimeline;
    use App\Support\JalaliDate;

    $request = RequestTimeline::load($getRecord());
    $attachments = $request->attachments->sortByDesc('id')->values();
    $canUpload = auth()->user()?->can('addAttachment', $request) ?? false;
    $canInternal = auth()->user()?->can('uploadInternalAttachment', $request) ?? false;
    $maxFiles = app(\App\Services\SettingsService::class)->getInt('max_upload_files', 5);
    $remainingSlots = max(0, $maxFiles - $attachments->count());
@endphp

@if ($canUpload && $remainingSlots > 0)
    <div class="mb-6 rounded-2xl border border-dashed border-primary-300 bg-primary-50/40 p-5 dark:border-primary-500/30 dark:bg-primary-500/5">
        <h4 class="mb-3 text-sm font-bold text-gray-900 dark:text-white">افزودن مدرک</h4>
        <form wire:submit="saveTabAttachments" class="space-y-4">
            <div class="grid gap-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    انتخاب فایل‌ها (حداکثر {{ $remainingSlots }} فایل)
                    <input
                        type="file"
                        wire:model="tabAttachmentFiles"
                        multiple
                        accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                        class="mt-1 block w-full text-sm"
                    >
                </label>
                @error('tabAttachmentFiles')
                    <p class="text-sm text-danger-600">{{ $message }}</p>
                @enderror
                @error('tabAttachmentFiles.*')
                    <p class="text-sm text-danger-600">{{ $message }}</p>
                @enderror
                @if ($canInternal)
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        نوع مدرک
                        <select wire:model="tabAttachmentVisibility" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                            <option value="customer">قابل نمایش به مشتری</option>
                            <option value="internal">داخلی (فقط کارکنان)</option>
                        </select>
                    </label>
                @endif
            </div>
            <x-filament::button type="submit" size="sm" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveTabAttachments,tabAttachmentFiles">بارگذاری</span>
                <span wire:loading wire:target="saveTabAttachments,tabAttachmentFiles">در حال بارگذاری…</span>
            </x-filament::button>
        </form>
    </div>
@elseif ($canUpload && $remainingSlots === 0)
    <p class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
        سقف تعداد فایل‌های این پرونده ({{ $maxFiles }}) تکمیل شده است.
    </p>
@endif

@if ($attachments->isEmpty())
    <p class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-white/10">مدرکی پیوست نشده است.</p>
@else
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($attachments as $attachment)
            @php
                $mime = (string) $attachment->mime_type;
                [$icon, $tint] = match (true) {
                    str_starts_with($mime, 'image/') => ['heroicon-o-photo', 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300'],
                    $mime === 'application/pdf' => ['heroicon-o-document-text', 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300'],
                    default => ['heroicon-o-paper-clip', 'bg-slate-100 text-slate-600 dark:bg-white/5 dark:text-slate-300'],
                };
                $internal = $attachment->visibility === AttachmentVisibility::Internal;
            @endphp
            <a
                href="{{ route('staff.attachments.show', $attachment) }}"
                target="_blank"
                class="group flex items-start gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-950/5 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-indigo-300 dark:bg-gray-900 dark:ring-white/10"
            >
                <span @class(['flex h-12 w-12 shrink-0 items-center justify-center rounded-xl', $tint])>
                    <x-filament::icon :icon="$icon" class="h-6 w-6" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-slate-900 group-hover:text-indigo-600 dark:text-white" dir="auto">{{ $attachment->original_name }}</span>
                    <span class="mt-1 block text-xs text-slate-500">{{ $attachment->category->label() }} • {{ RequestTimeline::fileSize($attachment->size) }}</span>
                    <span class="mt-1 block text-xs text-slate-500">
                        {{ $attachment->uploadedBy?->name ?? $request->customer?->full_name ?? 'مشتری' }}
                        • <span dir="ltr">{{ JalaliDate::format($attachment->created_at) }}</span>
                    </span>
                    <span @class(['mt-2 inline-block rounded-md px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset', RequestTimeline::tone($internal ? 'gray' : 'primary')])>
                        {{ $attachment->visibility->label() }}
                    </span>
                </span>
                <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-5 w-5 shrink-0 text-slate-400 group-hover:text-indigo-600" />
            </a>
        @endforeach
    </div>
@endif
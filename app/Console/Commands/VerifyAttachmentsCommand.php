<?php

namespace App\Console\Commands;

use App\Models\RequestAttachment;
use App\Support\PersistentStoragePath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VerifyAttachmentsCommand extends Command
{
    protected $signature = 'crm:verify-attachments {--missing : فقط ردیف‌های بدون فایل روی دیسک}';

    protected $description = 'بررسی وجود فایل پیوست‌ها روی دیسک private (تشخیص 404 دانلود)';

    public function handle(): int
    {
        $root = (string) config('filesystems.disks.local.root');
        $this->info('مسیر دیسک private: '.$root);

        if (! is_dir($root)) {
            $this->error('پوشه وجود ندارد. دیسک persistent را mount کنید یا CRM_STORAGE_PRIVATE_ROOT را درست تنظیم کنید.');

            return self::FAILURE;
        }

        if (! is_writable($root)) {
            $this->error('پوشه قابل نوشتن نیست (permission). آپلود هم احتمالاً شکست می‌خورد.');

            return self::FAILURE;
        }

        $disk = Storage::disk('local');
        $missing = 0;
        $total = 0;

        RequestAttachment::query()
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($disk, &$missing, &$total): void {
                foreach ($rows as $attachment) {
                    $total++;
                    $exists = $disk->exists($attachment->storage_path)
                        || PersistentStoragePath::existingFilePath($root, $attachment->storage_path) !== null;

                    if ($exists) {
                        if (! $this->option('missing')) {
                            $this->line('#'.$attachment->id.' OK  '.$attachment->storage_path);
                        }

                        continue;
                    }

                    $missing++;
                    $this->warn('#'.$attachment->id.' MISSING  request='.$attachment->service_request_id.'  '.$attachment->original_name);
                    $this->line('         path: '.$attachment->storage_path);
                }
            });

        $this->newLine();
        $this->info("جمع: {$total} — موجود: ".($total - $missing)." — گم‌شده روی دیسک: {$missing}");

        if ($missing > 0) {
            $this->warn('رکورد DB هست ولی فایل فیزیکی نیست (۴۰۴ دانلود). معمولاً بعد از mount دیسک خالی یا عوض کردن مسیر storage رخ می‌دهد.');
        }

        return $missing > 0 ? self::FAILURE : self::SUCCESS;
    }
}
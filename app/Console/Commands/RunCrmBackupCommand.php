<?php

namespace App\Console\Commands;

use App\Services\AuditLogService;
use App\Services\CrmBackupService;
use Illuminate\Console\Command;

class RunCrmBackupCommand extends Command
{
    protected $signature = 'crm:backup {--manual : بکاپ دستی (برچسب manual)}';

    protected $description = 'بکاپ فشرده از پایگاه داده و فایل‌های private (و public) با نگه‌داری محدود';

    public function handle(CrmBackupService $backups, AuditLogService $audit): int
    {
        $triggeredBy = $this->option('manual') ? 'manual' : 'schedule';

        try {
            $result = $backups->run($triggeredBy);

            $audit->record('backup.created', null, 'success', [
                'filename' => $result['filename'],
                'bytes' => $result['bytes'],
                'triggered_by' => $triggeredBy,
            ]);

            $this->info('بکاپ ساخته شد: '.$result['filename'].' ('.number_format($result['bytes'] / 1024, 1).' KiB)');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $audit->record('backup.failed', null, 'failure', [
                'message' => $exception->getMessage(),
                'triggered_by' => $triggeredBy,
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
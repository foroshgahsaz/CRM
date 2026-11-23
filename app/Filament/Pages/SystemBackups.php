<?php

namespace App\Filament\Pages;

use App\Services\AuditLogService;
use App\Services\CrmBackupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;
use UnitEnum;

class SystemBackups extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'بکاپ سامانه';

    protected static ?string $title = 'بکاپ پایگاه و فایل‌ها';

    protected static string|UnitEnum|null $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.system-backups';

    /**
     * @var list<array{filename: string, path: string, bytes: int, created_at: string}>
     */
    public array $backups = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.backup') ?? false;
    }

    public function mount(CrmBackupService $backups): void
    {
        $this->refreshBackups($backups);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runBackup')
                ->label('بکاپ اکنون')
                ->icon('heroicon-o-arrow-down-tray')
                ->requiresConfirmation()
                ->modalDescription('پایگاه داده و پوشهٔ مدارک خصوصی (و public) فشرده می‌شوند. حداکثر '.(int) config('crm.backup.retention', 2).' نسخهٔ آخر نگه داشته می‌شود.')
                ->action(fn () => $this->runManualBackup()),
        ];
    }

    public function runManualBackup(): void
    {
        $user = auth()->user();

        try {
            $result = app(CrmBackupService::class)->run('manual', $user);

            app(AuditLogService::class)->record('backup.created', null, 'success', [
                'filename' => $result['filename'],
                'bytes' => $result['bytes'],
                'triggered_by' => 'manual',
            ], $user);

            $this->refreshBackups(app(CrmBackupService::class));

            Notification::make()
                ->title('بکاپ آماده شد')
                ->body($result['filename'])
                ->success()
                ->send();
        } catch (Throwable $exception) {
            app(AuditLogService::class)->record('backup.failed', null, 'failure', [
                'message' => $exception->getMessage(),
                'triggered_by' => 'manual',
            ], $user);

            Notification::make()
                ->title('بکاپ ناموفق')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function refreshBackups(CrmBackupService $backups): void
    {
        $this->backups = $backups->list();
    }
}
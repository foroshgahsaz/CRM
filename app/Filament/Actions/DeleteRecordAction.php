<?php

namespace App\Filament\Actions;

use App\Exceptions\WorkflowException;
use App\Services\RecordDeletionService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction as FilamentDeleteAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DeleteRecordAction
{
    public static function make(): FilamentDeleteAction
    {
        return FilamentDeleteAction::make()
            ->modalDescription(fn (Model $record): string => app(RecordDeletionService::class)->warning($record))
            ->using(function (Model $record, FilamentDeleteAction $action): bool {
                try {
                    app(RecordDeletionService::class)->delete($record, auth()->user());

                    return true;
                } catch (WorkflowException|AuthorizationException $exception) {
                    $action->failureNotificationTitle($exception->getMessage() !== '' ? $exception->getMessage() : 'دسترسی لازم برای حذف وجود ندارد.');

                    return false;
                }
            });
    }

    public static function bulk(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->modalDescription('رکوردهای انتخاب‌شده و وابستگی‌های آن‌ها برای همیشه حذف می‌شوند. این عملیات قابل بازگشت نیست.')
            ->authorizeIndividualRecords('delete')
            ->using(function (DeleteBulkAction $action, Collection $records): void {
                $service = app(RecordDeletionService::class);

                $records->each(function (Model $record) use ($action, $service): void {
                    try {
                        $service->delete($record, auth()->user());
                    } catch (WorkflowException|AuthorizationException $exception) {
                        $message = $exception->getMessage() !== '' ? $exception->getMessage() : 'دسترسی لازم برای حذف وجود ندارد.';
                        $action->reportBulkProcessingFailure(md5($message), $message);
                    }
                });
            });
    }

    public static function bulkGroup(): BulkActionGroup
    {
        return BulkActionGroup::make([self::bulk()]);
    }
}

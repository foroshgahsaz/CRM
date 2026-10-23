<?php

namespace App\Filament\Actions;

use App\Exceptions\WorkflowException;
use App\Models\InventoryAllocation;
use App\Models\ServiceRequest;
use App\Models\WarehouseProduct;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;

class InventoryActions
{
    public static function allocateToRequest(): Action
    {
        return Action::make('allocateToRequest')
            ->label('ارجاع به درخواست')
            ->icon('heroicon-o-arrow-right-circle')
            ->color('success')
            ->visible(fn (): bool => auth()->user()?->can('inventory.allocate') ?? false)
            ->disabled(fn (WarehouseProduct $record): bool => ! $record->isInStock())
            ->modalHeading(fn (WarehouseProduct $record): string => 'ارجاع یک عدد «'.$record->displayLabel().'»')
            ->modalDescription(fn (WarehouseProduct $record): string => 'موجودی فعلی: '.$record->quantity.' عدد. با ثبت ارجاع، یک عدد از موجودی کم می‌شود.')
            ->schema([
                Select::make('service_request_id')
                    ->label('درخواست (کد رهگیری یا نام کالا)')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => self::searchOpenRequests($search))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => self::requestLabel(ServiceRequest::query()->find($value)))
                    ->options(fn (): array => self::searchOpenRequests('')),
                Textarea::make('note')->label('توضیح')->maxLength(1000),
            ])
            ->action(function (WarehouseProduct $record, array $data): void {
                $request = ServiceRequest::query()->find($data['service_request_id'] ?? null);

                if (! $request) {
                    Notification::make()->title('درخواست انتخاب‌شده یافت نشد.')->danger()->send();

                    return;
                }

                self::run(
                    fn () => app(InventoryService::class)->allocate($record, $request, auth()->user(), $data['note'] ?? null),
                    'یک عدد کالا به درخواست '.$request->tracking_code.' ارجاع شد.',
                );
            });
    }

    public static function allocateFromRequest(): Action
    {
        return Action::make('allocateFromWarehouse')
            ->label('ارجاع از انبار')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->schema([
                Select::make('warehouse_product_id')
                    ->label('کالا (نام یا کد)')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => self::searchInStockProducts($search))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => self::productLabel(WarehouseProduct::query()->with('warehouse')->find($value)))
                    ->options(fn (): array => self::searchInStockProducts('')),
                Textarea::make('note')->label('توضیح')->maxLength(1000),
            ]);
    }

    public static function cancelAllocation(): Action
    {
        return Action::make('cancelAllocation')
            ->label('لغو ارجاع')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->visible(fn (InventoryAllocation $record): bool => auth()->user()?->can('cancel', $record) ?? false)
            ->requiresConfirmation()
            ->modalDescription('با لغو ارجاع، یک عدد به موجودی انبار بازمی‌گردد.')
            ->schema([Textarea::make('reason')->label('دلیل لغو')->required()->minLength(3)->maxLength(1000)])
            ->action(fn (InventoryAllocation $record, array $data) => self::run(
                fn () => app(InventoryService::class)->cancel($record, auth()->user(), (string) $data['reason']),
                'ارجاع لغو شد و موجودی بازگشت.',
            ));
    }

    /**
     * @return array<int, string>
     */
    public static function searchOpenRequests(string $search): array
    {
        $search = trim($search);

        return ServiceRequest::query()
            ->open()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('tracking_code', 'like', '%'.$search.'%')
                    ->orWhere('product_name', 'like', '%'.$search.'%');
            }))
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (ServiceRequest $request): array => [$request->id => self::requestLabel($request)])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function searchInStockProducts(string $search): array
    {
        $search = trim($search);

        return WarehouseProduct::query()
            ->with('warehouse')
            ->where('quantity', '>', 0)
            ->whereHas('warehouse', fn ($query) => $query->where('is_active', true))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            }))
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (WarehouseProduct $product): array => [$product->id => self::productLabel($product)])
            ->all();
    }

    public static function run(callable $callback, string $success): bool
    {
        try {
            $callback();
            Notification::make()->title($success)->success()->send();

            return true;
        } catch (WorkflowException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        } catch (AuthorizationException $exception) {
            Notification::make()->title($exception->getMessage() !== '' ? $exception->getMessage() : 'دسترسی لازم برای این عملیات وجود ندارد.')->danger()->send();
        }

        return false;
    }

    private static function requestLabel(?ServiceRequest $request): ?string
    {
        if (! $request) {
            return null;
        }

        return $request->tracking_code.($request->product_name ? ' — '.$request->product_name : '');
    }

    private static function productLabel(?WarehouseProduct $product): ?string
    {
        if (! $product) {
            return null;
        }

        return $product->displayLabel().' — '.$product->warehouse?->name.' — موجودی: '.$product->quantity;
    }
}

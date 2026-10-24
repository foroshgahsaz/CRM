<?php

namespace App\Filament\Resources\ServiceRequests\Tables;

use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Filament\Actions\DeleteRecordAction;
use App\Models\OrganizationalUnit;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\JalaliDate;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('tracking_code')->label('کد رهگیری')->searchable()->copyable()
                    ->weight(fn (ServiceRequest $record): string => self::unread($record) ? 'bold' : 'normal')
                    ->description(fn (ServiceRequest $record): ?string => self::unread($record) ? 'مشاهده‌نشده' : null),
                TextColumn::make('requestType.name')->label('نوع')->searchable(),
                TextColumn::make('customer.full_name')->label('مشتری')->searchable(),
                TextColumn::make('customer.mobile')->label('موبایل')->searchable()->visible(fn (): bool => auth()->user()?->can('requests.view_sensitive') ?? false),
                TextColumn::make('status')->label('وضعیت')->formatStateUsing(fn (RequestStatus $state): string => $state->label())->badge(),
                TextColumn::make('priority')->label('اولویت')->formatStateUsing(fn (RequestPriority $state): string => $state->label()),
                TextColumn::make('currentOwner.name')->label('مسئول')->placeholder('بدون مسئول'),
                TextColumn::make('currentUnit.name')->label('واحد')->placeholder('—'),
                TextColumn::make('submitted_at')->label('ثبت')->formatStateUsing(fn ($state): string => JalaliDate::format($state)),
                TextColumn::make('due_at')->label('سررسید')->formatStateUsing(fn ($state): string => JalaliDate::format($state))
                    ->description(fn (ServiceRequest $record): ?string => $record->isOverdue() ? 'معوق' : null),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(collect(RequestStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])),
                SelectFilter::make('priority')->label('اولویت')->options(collect(RequestPriority::cases())->mapWithKeys(fn ($priority) => [$priority->value => $priority->label()])),
                SelectFilter::make('request_type_id')->label('نوع')->relationship('requestType', 'name'),
                SelectFilter::make('current_owner_id')->label('مسئول')->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('current_unit_id')->label('واحد')->options(fn () => OrganizationalUnit::query()->orderBy('name')->pluck('name', 'id')),
                TernaryFilter::make('overdue')->label('معوق')->queries(
                    true: fn (Builder $query) => $query->overdue(),
                    false: fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('due_at')->orWhere('due_at', '>=', now())),
                ),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteRecordAction::make(),
            ])
            ->toolbarActions([
                DeleteRecordAction::bulkGroup(),
            ]);
    }

    private static function unread(ServiceRequest $record): bool
    {
        return $record->current_owner_id === auth()->id()
            && $record->currentAssignment?->first_viewed_at === null;
    }
}

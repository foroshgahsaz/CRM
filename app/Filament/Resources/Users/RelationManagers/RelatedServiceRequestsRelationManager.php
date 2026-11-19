<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\RequestStatus;
use App\Filament\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Services\UserRelatedRequestsService;
use App\Support\JalaliDate;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RelatedServiceRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'ownedServiceRequests';

    protected static ?string $title = 'پرونده‌های مرتبط';

    public function isReadOnly(): bool
    {
        return true;
    }

    protected function getTableQuery(): ?Builder
    {
        /** @var \App\Models\User $owner */
        $owner = $this->getOwnerRecord();

        return app(UserRelatedRequestsService::class)->queryForUser($owner);
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('پرونده‌ای یافت نشد')
            ->emptyStateDescription('درخواست‌های ثبت‌شده با موبایل این کاربر، ثبت داخلی، مسئولیت یا ارجاع اینجا نمایش داده می‌شود.')
            ->columns([
                TextColumn::make('tracking_code')->label('کد رهگیری')->searchable()->copyable(),
                TextColumn::make('requestType.name')->label('نوع')->placeholder('—'),
                TextColumn::make('customer.full_name')->label('مشتری')->placeholder('—'),
                TextColumn::make('customer.mobile')->label('موبایل مشتری')->placeholder('—'),
                TextColumn::make('status')->label('وضعیت')->formatStateUsing(fn (RequestStatus $state): string => $state->label())->badge(),
                TextColumn::make('currentOwner.name')->label('مسئول فعلی')->placeholder('—'),
                TextColumn::make('submitted_at')->label('ثبت')->formatStateUsing(fn ($state): string => JalaliDate::format($state)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (ServiceRequest $record): string => ServiceRequestResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $actor = auth()->user();

        return $actor !== null && ($actor->can('users.view') || $actor->can('users.manage'));
    }
}
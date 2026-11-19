<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\ServiceRequest;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'داشبورد';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createRequest')
                ->label('ثبت درخواست جدید')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(ServiceRequestResource::getUrl('create'))
                ->visible(fn (): bool => auth()->user()?->can('create', ServiceRequest::class) ?? false),
        ];
    }
}
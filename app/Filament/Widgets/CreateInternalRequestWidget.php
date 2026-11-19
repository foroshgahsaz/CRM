<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\ServiceRequest;
use Filament\Widgets\Widget;

class CreateInternalRequestWidget extends Widget
{
    protected static string $view = 'filament.widgets.create-internal-request';

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('create', ServiceRequest::class);
    }

    public function createUrl(): string
    {
        return ServiceRequestResource::getUrl('create');
    }
}
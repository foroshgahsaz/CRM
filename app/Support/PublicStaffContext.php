<?php

namespace App\Support;

use App\Filament\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\User;
use Filament\Facades\Filament;

class PublicStaffContext
{
    /**
     * @return array<string, mixed>|null
     */
    public static function resolve(): ?array
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->is_active || ! $user->roles()->exists()) {
            return null;
        }

        $panel = Filament::getPanel('admin');
        $previous = Filament::getCurrentPanel();
        Filament::setCurrentPanel($panel);

        $createUrl = null;
        $requestsIndexUrl = null;

        try {
            $createUrl = $user->can('create', \App\Models\ServiceRequest::class)
                ? ServiceRequestResource::getUrl('create')
                : null;
            $requestsIndexUrl = ($user->can('requests.view_related') || $user->can('requests.view_all'))
                ? ServiceRequestResource::getUrl('index')
                : null;
        } finally {
            Filament::setCurrentPanel($previous);
        }

        $roleNames = $user->getRoleNames()
            ->map(fn (string $name): string => PermissionCatalog::roles()[$name] ?? $name)
            ->values()
            ->all();

        return [
            'user' => $user,
            'name' => $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'roles_label' => implode('، ', $roleNames),
            'unit_name' => $user->organizationalUnit?->name,
            'login_url' => $panel->getLoginUrl(),
            'dashboard_url' => $panel->getUrl(),
            'profile_url' => $panel->getProfileUrl(),
            'logout_url' => $panel->getLogoutUrl(),
            'create_request_url' => $createUrl,
            'requests_index_url' => $requestsIndexUrl ?? null,
        ];
    }

    public static function loginUrl(): string
    {
        return Filament::getPanel('admin')->getLoginUrl();
    }
}
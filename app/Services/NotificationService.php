<?php

namespace App\Services;

use App\Models\NotificationDelivery;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\StaffAlertNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * @param  Collection<int, User>  $users
     */
    public function notify(
        Collection $users,
        string $type,
        string $title,
        string $body,
        ?ServiceRequest $request = null,
        ?int $actorId = null,
        ?string $dedupe = null,
    ): void {
        $users
            ->filter(fn (User $user): bool => $user->is_active && $user->id !== $actorId)
            ->unique('id')
            ->each(function (User $user) use ($type, $title, $body, $request, $dedupe): void {
                $key = $dedupe ?? ($type.':'.($request?->id ?? '0').':'.$user->id);

                if (NotificationDelivery::query()->where('idempotency_key', $key.':'.$user->id)->exists()) {
                    return;
                }

                $user->notify(new StaffAlertNotification(
                    $title,
                    $body,
                    $request ? url('/admin/service-requests/'.$request->id) : null,
                ));

                NotificationDelivery::query()->create([
                    'recipient_id' => $user->id,
                    'notification_type' => $type,
                    'channel' => 'database',
                    'service_request_id' => $request?->id,
                    'status' => 'delivered',
                    'delivered_at' => now(),
                    'idempotency_key' => $key.':'.$user->id,
                ]);
            });
    }

    /**
     * @return Collection<int, User>
     */
    public function usersWith(string $permission): Collection
    {
        return User::query()->permission($permission)->where('is_active', true)->get();
    }
}

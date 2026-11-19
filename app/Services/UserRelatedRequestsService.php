<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserRelatedRequestsService
{
    /**
     * @return Builder<ServiceRequest>
     */
    public function queryForUser(User $user): Builder
    {
        $ids = $this->relatedIds($user);

        if ($ids === []) {
            return ServiceRequest::query()->whereRaw('0 = 1');
        }

        return ServiceRequest::query()
            ->whereIn('id', $ids)
            ->with(['requestType', 'customer', 'currentOwner'])
            ->orderByDesc('submitted_at');
    }

    /**
     * پرونده‌های باز که حذف کاربر را مسدود می‌کنند (مسئول فعلی یا ارجاع فعال).
     *
     * @return Collection<int, ServiceRequest>
     */
    public function blockingOpenRequests(User $user): Collection
    {
        $ownedIds = ServiceRequest::query()->open()
            ->where('current_owner_id', $user->id)
            ->pluck('id');

        $assignedIds = ServiceRequest::query()->open()
            ->whereHas('assignments', fn (Builder $query) => $query
                ->where('to_user_id', $user->id)
                ->where('status', AssignmentStatus::Active->value))
            ->pluck('id');

        $ids = $ownedIds->merge($assignedIds)->unique()->values()->all();

        if ($ids === []) {
            return new Collection;
        }

        return ServiceRequest::query()
            ->whereIn('id', $ids)
            ->with(['requestType', 'customer'])
            ->orderByDesc('submitted_at')
            ->get();
    }

    /**
     * @return list<int>
     */
    public function relatedIds(User $user): array
    {
        $ids = collect();

        $ids = $ids->merge(
            ServiceRequest::query()->where('current_owner_id', $user->id)->pluck('id')
        );

        $ids = $ids->merge(
            ServiceRequest::query()
                ->whereHas('assignments', fn (Builder $query) => $query->where('to_user_id', $user->id))
                ->pluck('id')
        );

        $ids = $ids->merge(
            ServiceRequest::query()->where('internal_submitted_by_id', $user->id)->pluck('id')
        );

        if (filled($user->mobile)) {
            $ids = $ids->merge(
                ServiceRequest::query()
                    ->whereHas('customer', fn (Builder $query) => $query->where('mobile', $user->mobile))
                    ->pluck('id')
            );
        }

        return $ids->filter()->unique()->values()->all();
    }

    public function deleteBlockerMessage(User $user): string
    {
        $open = $this->blockingOpenRequests($user);

        if ($open->isEmpty()) {
            return '';
        }

        $lines = $open->map(function (ServiceRequest $request): string {
            $status = $request->status->label();
            $customer = $request->customer?->full_name ?? '—';

            return sprintf('• %s — %s — مشتری: %s', $request->tracking_code, $status, $customer);
        })->implode("\n");

        return "پرونده‌های باز مرتبط ({$open->count()} مورد):\n".$lines."\n\nاز تب «پرونده‌های مرتبط» در همین صفحه هر پرونده را باز کنید، مسئول/ارجاع را عوض کنید و دوباره حذف را امتحان کنید.";
    }
}
<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class OperationsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->can('requests.view_all') || $user->can('requests.view_related'));
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $query = ServiceRequest::query();

        if ($user) {
            $query->visibleTo($user);
        }

        $count = function (callable $scope) use ($query): int {
            $builder = clone $query;
            $scope($builder);

            return $builder->count();
        };

        return [
            Stat::make('کل درخواست‌ها', (clone $query)->count()),
            Stat::make('جدید ۲۴ ساعت', $count(fn (Builder $builder) => $builder->where('submitted_at', '>=', now()->subDay()))),
            Stat::make('تخصیص‌نیافته', $count(fn (Builder $builder) => $builder->where('status', RequestStatus::PendingAssignment))),
            Stat::make('در حال رسیدگی', $count(fn (Builder $builder) => $builder->whereIn('status', [
                RequestStatus::Assigned,
                RequestStatus::InProgress,
                RequestStatus::AwaitingUnitClaim,
                RequestStatus::Reopened,
            ]))),
            Stat::make('منتظر مشتری', $count(fn (Builder $builder) => $builder->where('status', RequestStatus::WaitingCustomer))),
            Stat::make('معوق', $count(fn (Builder $builder) => $builder->overdue())),
            Stat::make('منتظر تأیید نهایی', $count(fn (Builder $builder) => $builder->where('status', RequestStatus::PendingFinalApproval))),
            Stat::make('تأییدشده و باز', $count(fn (Builder $builder) => $builder->where('status', RequestStatus::Approved))),
            Stat::make('بسته‌شده', $count(fn (Builder $builder) => $builder->where('status', RequestStatus::Closed))),
            Stat::make('بایگانی', $count(fn (Builder $builder) => $builder->where('status', RequestStatus::Archived))),
            Stat::make('مشاهده‌نشده من', $user ? (clone $query)
                ->where('current_owner_id', $user->id)
                ->whereHas('currentAssignment', fn (Builder $assignment) => $assignment->whereNull('first_viewed_at'))
                ->count() : 0)
                ->description('مأموریت فعال که هنوز توسط شما باز نشده'),
        ];
    }
}

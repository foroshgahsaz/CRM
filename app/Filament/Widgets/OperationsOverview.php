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

    protected ?string $heading = 'نمای کلی عملیات';

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

        $spark = fn (callable $scope): array => $this->sparkline($query, $scope);

        $total = (clone $query)->count();
        $inProgress = $count(fn (Builder $builder) => $builder->whereIn('status', [
            RequestStatus::Assigned,
            RequestStatus::InProgress,
            RequestStatus::AwaitingUnitClaim,
            RequestStatus::Reopened,
        ]));
        $overdue = $count(fn (Builder $builder) => $builder->overdue());
        $mineUnread = $user ? (clone $query)
            ->where('current_owner_id', $user->id)
            ->whereHas('currentAssignment', fn (Builder $assignment) => $assignment->whereNull('first_viewed_at'))
            ->count() : 0;

        return [
            Stat::make('کل پرونده‌ها', number_format($total))
                ->description('همهٔ درخواست‌های قابل مشاهده')
                ->descriptionIcon('heroicon-m-inbox-stack')
                ->color('primary')
                ->chart($spark(fn (Builder $b) => $b)),
            Stat::make('در حال رسیدگی', number_format($inProgress))
                ->description('ارجاع‌شده یا در جریان کار')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info')
                ->chart($spark(fn (Builder $b) => $b->whereIn('status', [
                    RequestStatus::Assigned,
                    RequestStatus::InProgress,
                    RequestStatus::AwaitingUnitClaim,
                    RequestStatus::Reopened,
                ]))),
            Stat::make('معوق', number_format($overdue))
                ->description('از مهلت رسیدگی گذشته')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : 'success')
                ->chart($spark(fn (Builder $b) => $b->overdue())),
            Stat::make('مشاهده‌نشدهٔ من', number_format($mineUnread))
                ->description('مأموریت فعال باز نشده')
                ->descriptionIcon('heroicon-m-eye-slash')
                ->color($mineUnread > 0 ? 'warning' : 'gray')
                ->chart($spark(fn (Builder $b) => $b->where('current_owner_id', $user?->id))),
        ];
    }

    /**
     * @param  Builder<ServiceRequest>  $base
     * @return list<int>
     */
    private function sparkline(Builder $base, callable $scope): array
    {
        $points = [];

        for ($day = 6; $day >= 0; $day--) {
            $from = now()->subDays($day)->startOfDay();
            $to = now()->subDays($day)->endOfDay();
            $builder = clone $base;
            $scope($builder);

            $points[] = $builder->whereBetween('submitted_at', [$from, $to])->count();
        }

        return $points;
    }
}

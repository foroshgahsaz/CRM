<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use Filament\Widgets\ChartWidget;

class RequestStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'توزیع وضعیت پرونده‌ها';

    protected ?string $description = 'سهم هر وضعیت از کل درخواست‌های قابل مشاهده';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->can('requests.view_all') || $user->can('requests.view_related'));
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $query = ServiceRequest::query();
        $user = auth()->user();

        if ($user) {
            $query->visibleTo($user);
        }

        $labels = [];
        $data = [];
        $colors = [];

        foreach ([
            RequestStatus::PendingAssignment,
            RequestStatus::AwaitingUnitClaim,
            RequestStatus::Assigned,
            RequestStatus::InProgress,
            RequestStatus::Reopened,
            RequestStatus::WaitingCustomer,
            RequestStatus::PendingFinalApproval,
            RequestStatus::Approved,
            RequestStatus::Closed,
            RequestStatus::Archived,
        ] as $status) {
            $count = (clone $query)->where('status', $status)->count();

            if ($count === 0) {
                continue;
            }

            $labels[] = $status->label();
            $data[] = $count;
            $colors[] = match ($status) {
                RequestStatus::PendingAssignment, RequestStatus::AwaitingUnitClaim => '#f59e0b',
                RequestStatus::Assigned, RequestStatus::InProgress, RequestStatus::Reopened => '#0ea5e9',
                RequestStatus::WaitingCustomer => '#fb923c',
                RequestStatus::PendingFinalApproval => '#6366f1',
                RequestStatus::Approved => '#10b981',
                RequestStatus::Closed => '#64748b',
                RequestStatus::Archived => '#94a3b8',
            };
        }

        return [
            'datasets' => [[
                'data' => $data,
                'backgroundColor' => $colors,
                'borderWidth' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'rtl' => true,
                    'labels' => ['boxWidth' => 12, 'padding' => 14],
                ],
            ],
        ];
    }
}

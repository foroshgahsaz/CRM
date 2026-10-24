<?php

namespace App\Filament\Widgets;

use App\Models\ServiceRequest;
use App\Support\JalaliDate;
use Filament\Widgets\ChartWidget;

class RequestsSubmittedChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'روند ثبت درخواست';

    protected ?string $description = '۱۴ روز اخیر';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected ?string $maxHeight = '280px';

    protected string $color = 'info';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->can('requests.view_all') || $user->can('requests.view_related'));
    }

    protected function getType(): string
    {
        return 'line';
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

        for ($day = 13; $day >= 0; $day--) {
            $from = now()->subDays($day)->startOfDay();
            $to = now()->subDays($day)->endOfDay();
            $labels[] = JalaliDate::format($from, 'm/d');
            $data[] = (clone $query)->whereBetween('submitted_at', [$from, $to])->count();
        }

        return [
            'datasets' => [[
                'label' => 'ثبت شده',
                'data' => $data,
                'fill' => true,
                'tension' => 0.35,
                'borderWidth' => 2,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
            'plugins' => ['legend' => ['display' => false]],
        ];
    }
}

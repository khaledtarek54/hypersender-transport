<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Models\Enums\TripStatus;
use Filament\Widgets\ChartWidget;

class TripsByStatusWidget extends ChartWidget
{
    protected static ?string $heading = 'Trips by Status (Last 30 Days)';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $since = now()->subDays(30);

        $statuses = [
            TripStatus::Scheduled,
            TripStatus::InProgress,
            TripStatus::Completed,
            TripStatus::Cancelled,
        ];

        $labels = array_map(fn (TripStatus $s) => $s->label(), $statuses);

        $counts = [];
        foreach ($statuses as $status) {
            $counts[] = Trip::where('status', $status->value)
                ->where('created_at', '>=', $since)
                ->count();
        }

        $colors = [
            'rgba(234, 179, 8, 0.7)',   // warning (scheduled)
            'rgba(59, 130, 246, 0.7)',  // primary (in_progress)
            'rgba(34, 197, 94, 0.7)',   // success (completed)
            'rgba(239, 68, 68, 0.7)',   // danger (cancelled)
        ];

        $borderColors = [
            'rgba(234, 179, 8, 1)',
            'rgba(59, 130, 246, 1)',
            'rgba(34, 197, 94, 1)',
            'rgba(239, 68, 68, 1)',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Trips',
                    'data' => $counts,
                    'backgroundColor' => $colors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}



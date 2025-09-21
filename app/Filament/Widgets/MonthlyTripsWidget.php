<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MonthlyTripsWidget extends ChartWidget
{
    protected static ?string $heading = 'Monthly Trips';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        
        // Get last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M Y');
            
            $trips = Trip::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
                
            $data[] = $trips;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Trips',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}

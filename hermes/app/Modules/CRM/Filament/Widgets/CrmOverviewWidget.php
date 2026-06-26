<?php

namespace App\Modules\CRM\Filament\Widgets;

use App\Modules\CRM\Models\Deal;
use App\Modules\CRM\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $newLeads = Lead::where('status', 'new')->count();
        $activeDealsCount = Deal::where('status', 'active')->count();
        $pipelineVolume = Deal::where('status', 'active')->sum('value');
        $wonRevenue = Deal::where('status', 'won')->sum('value');

        return [
            Stat::make('New Leads', $newLeads)
                ->description('Inbound leads awaiting contact')
                ->descriptionIcon('heroicon-m-funnel')
                ->color('info')
                ->chart([3, 7, 5, 4, 8, $newLeads]),

            Stat::make('Active Deals', $activeDealsCount)
                ->description('Deals currently in sales pipeline')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning')
                ->chart([2, 4, 6, 5, 8, $activeDealsCount]),

            Stat::make('Pipeline Volume', '$' . number_format($pipelineVolume, 2))
                ->description('Total active pipeline valuation')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Closed Won Revenue', '$' . number_format($wonRevenue, 2))
                ->description('Total closed won deal revenues')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->chart([5000, 12000, 20000, 35000, 48000, $wonRevenue]),
        ];
    }
}

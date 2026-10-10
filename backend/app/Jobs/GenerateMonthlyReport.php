<?php

namespace App\Jobs;

use App\Models\Tontine;
use App\Services\ReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $reportService = app(ReportService::class);
        $month = now()->month;
        $year = now()->year;

        foreach (Tontine::query()->where('statut', '!=', 'archivee')->get() as $tontine) {
            $report = $reportService->getMonthlyReport($tontine->id, $month, $year);

            Log::info('Monthly tontine report generated', [
                'tontine_id' => $tontine->id,
                'month' => $month,
                'year' => $year,
                'contributions_count' => $report['contributions']['count'] ?? 0,
                'total' => $report['contributions']['total'] ?? 0,
            ]);
        }
    }
}

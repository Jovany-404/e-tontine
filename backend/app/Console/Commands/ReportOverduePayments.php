<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportOverduePayments extends Command
{
    protected $signature = 'payments:report-overdue';

    protected $description = 'Signale les échéances de cotisation en retard sans stocker leur statut.';

    public function handle(): int
    {
        $overdueByTontine = DB::table('contribution_schedules')
            ->join('cycles', 'cycles.id', '=', 'contribution_schedules.cycle_id')
            ->join('tontines', 'tontines.id', '=', 'cycles.tontine_id')
            ->where('contribution_schedules.status', '!=', 'paid')
            ->where('contribution_schedules.due_date', '<', today()->toDateString())
            ->groupBy('tontines.id', 'tontines.name')
            ->orderBy('tontines.name')
            ->get([
                'tontines.name as tontine_name',
                DB::raw('COUNT(contribution_schedules.id) as overdue_count'),
            ]);

        foreach ($overdueByTontine as $report) {
            $this->warn("{$report->tontine_name} : {$report->overdue_count} échéance(s) en retard.");
        }

        $total = $overdueByTontine->sum('overdue_count');

        Log::info('Rapport quotidien des échéances en retard.', [
            'overdue_schedules_count' => $total,
            'tontines_count' => $overdueByTontine->count(),
        ]);

        $this->info("Total : {$total} échéance(s) en retard.");

        return self::SUCCESS;
    }
}

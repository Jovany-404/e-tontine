<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Services\ReportService;
use App\Models\Tontine;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function tontine(int $tontineId): JsonResponse
    {
        $tontine = Tontine::find($tontineId);

        if (!$tontine || $tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $data = $this->reportService->getTontineReport($tontineId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function monthly(ReportFilterRequest $request, int $tontineId): JsonResponse
    {
        $tontine = Tontine::find($tontineId);

        if (!$tontine || $tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $data = $request->validated();

        $report = $this->reportService->getMonthlyReport(
            $tontineId,
            $data['mois'],
            $data['année']
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ], 200);
    }

    public function member(int $membershipId): JsonResponse
    {
        $membership = \App\Models\Membership::find($membershipId);

        if (!$membership || $membership->tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $data = $this->reportService->getMemberReport($membershipId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
}

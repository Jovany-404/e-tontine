<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTontineRequest;
use App\Http\Requests\UpdateTontineRequest;
use App\Http\Requests\ArchiveTontineRequest;
use App\Http\Requests\CloseTontineRequest;
use App\Http\Resources\TontineResource;
use App\Http\Resources\TontineCollection;
use App\Services\TontineService;
use App\Services\DashboardService;
use App\Models\Tontine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TontineController extends Controller
{
    protected TontineService $tontineService;
    protected DashboardService $dashboardService;

    public function __construct(TontineService $tontineService, DashboardService $dashboardService)
    {
        $this->tontineService = $tontineService;
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request): JsonResponse
    {
        $statut = $request->get('statut');

        $query = Tontine::query()->where('user_id', auth()->id());

        if ($statut) {
            $query->where('statut', $statut);
        }

        $tontines = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => new TontineCollection($tontines),
        ], 200);
    }

    public function store(StoreTontineRequest $request): JsonResponse
    {
        $data = $request->validated();

        $tontine = $this->tontineService->create($data);

        // Générer schedules automatiquement
        $this->tontineService->generateSchedules($tontine);

        return response()->json([
            'success' => true,
            'message' => 'Tontine créée avec succès',
            'data' => new TontineResource($tontine),
        ], 201);
    }

    public function show(Tontine $tontine): JsonResponse
    {
        if ($tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $stats = $this->tontineService->getStatistics($tontine);

        return response()->json([
            'success' => true,
            'data' => [
                'tontine' => new TontineResource($tontine),
                'statistics' => $stats,
            ],
        ], 200);
    }

    public function update(UpdateTontineRequest $request, Tontine $tontine): JsonResponse
    {
        if ($tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $data = $request->validated();

        $tontine = $this->tontineService->update($tontine, $data);

        return response()->json([
            'success' => true,
            'message' => 'Tontine modifiée avec succès',
            'data' => new TontineResource($tontine),
        ], 200);
    }

    public function archive(ArchiveTontineRequest $request, Tontine $tontine): JsonResponse
    {
        if ($tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $tontine = $this->tontineService->archive($tontine);

        return response()->json([
            'success' => true,
            'message' => 'Tontine archivée avec succès',
            'data' => new TontineResource($tontine),
        ], 200);
    }

    public function close(CloseTontineRequest $request, Tontine $tontine): JsonResponse
    {
        if ($tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $tontine = $this->tontineService->close($tontine);

        return response()->json([
            'success' => true,
            'message' => 'Tontine close avec succès',
            'data' => new TontineResource($tontine),
        ], 200);
    }

    public function delete(Tontine $tontine): JsonResponse
    {
        if ($tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $tontine->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tontine supprimée avec succès',
        ], 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Services\ScheduleService;
use App\Services\TontineService;
use App\Models\Schedule;
use App\Models\Tontine;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    protected ScheduleService $scheduleService;
    protected TontineService $tontineService;

    public function __construct(ScheduleService $scheduleService, TontineService $tontineService)
    {
        $this->scheduleService = $scheduleService;
        $this->tontineService = $tontineService;
    }

    public function index(int $tontineId): JsonResponse
    {
        $tontine = Tontine::find($tontineId);

        if (!$tontine || $tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $data = $this->scheduleService->getByTontine($tontineId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function store(StoreScheduleRequest $request, int $tontineId): JsonResponse
    {
        $tontine = Tontine::find($tontineId);

        if (!$tontine || $tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $data = $request->validated();

        $schedule = $this->scheduleService->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Schedule créé avec succès',
            'data' => new ScheduleResource($schedule),
        ], 201);
    }

    public function generate(int $tontineId): JsonResponse
    {
        $tontine = Tondatabase/seeders/user_seeder.phptine::find($tontineId);

        if (!$tontine || $tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $this->scheduleService->generateForTontine($tontine);

        return response()->json([
            'success' => true,
            'message' => 'Schedules générés avec succès',
        ], 200);
    }

    public function show(Schedule $schedule): JsonResponse
    {
        if ($schedule->tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $schedule->load('member')->toArray(),
        ], 200);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule): JsonResponse
    {
        if ($schedule->tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $data = $request->validated();

        $schedule = $this->scheduleService->update($schedule, $data);

        return response()->json([
            'success' => true,
            'message' => 'Schedule modifié avec succès',
            'data' => new ScheduleResource($schedule),
        ], 200);
    }

    public function realise(Schedule $schedule): JsonResponse
    {
        if ($schedule->tontine->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $schedule = $this->scheduleService->markAsRealise($schedule);

        return response()->json([
            'success' => true,
            'message' => 'Benefice réalisé avec succès',
            'data' => new ScheduleResource($schedule),
        ], 200);
    }

    public function getNext(int $tontineId): JsonResponse
    {
        $next = $this->scheduleService->getNextBeneficiary($tontineId);

        return response()->json([
            'success' => true,
            'data' => $next,
        ], 200);
    }
}

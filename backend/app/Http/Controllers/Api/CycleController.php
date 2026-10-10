<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cycle\StartCycleRequest;
use App\Models\Tontine;
use App\Services\CycleService;
use Illuminate\Http\JsonResponse;

class CycleController extends Controller
{
    public function __construct(private CycleService $cycles) {}

    public function start(StartCycleRequest $request, Tontine $tontine): JsonResponse
    {
        $result = $this->cycles->startFirst($tontine, $request->user());
        $cycle = $result['cycle'];

        return response()->json([
            'data' => [
                'number' => $cycle->number,
                'status' => $cycle->status->value,
                'started_at' => $cycle->started_at?->toIso8601String(),
                'members_count' => $result['members_count'],
                'schedules_count' => $result['schedules_count'],
            ],
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DrawResource;
use App\Models\Draw;
use App\Models\Tontine;
use App\Services\DrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DrawController extends Controller
{
    public function index(Tontine $tontine): JsonResponse
    {
        Gate::authorize('view', $tontine);

        $draws = Draw::query()
            ->where('tontine_id', $tontine->id)
            ->with(['cycle:id,number', 'winner.user:id,public_id,name'])
            ->orderBy('cycle_id')
            ->orderBy('round')
            ->get();

        return DrawResource::collection($draws)->response();
    }

    public function store(Request $request, Tontine $tontine, DrawService $draws): JsonResponse
    {
        Gate::authorize('draw', $tontine);

        $result = $draws->drawNext($tontine, $request->user());

        return response()->json([
            'data' => [
                'draw' => DrawResource::make($result['draw'])->resolve(),
                'cycle_completed' => $result['cycle_completed'],
            ],
        ], 201);
    }
}

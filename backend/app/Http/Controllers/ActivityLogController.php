<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Tontine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tontineId = $request->get('tontine_id');

        $query = ActivityLog::query()->orderBy('created_at', 'desc');

        if ($tontineId) {
            $tontine = Tontine::find($tontineId);

            if (!$tontine || $tontine->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé',
                ], 403);
            }

            $query->where('tontine_id', $tontineId);
        }

        $logs = $query->limit(100)->get();

        return response()->json([
            'success' => true,
            'data' => $logs->toArray(),
        ], 200);
    }
}

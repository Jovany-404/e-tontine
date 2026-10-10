<?php

namespace App\Http\Controllers\Api;

use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tontine\StoreTontineRequest;
use App\Http\Requests\Tontine\UpdateTontineRequest;
use App\Http\Resources\TontineResource;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Services\TontineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TontineController extends Controller
{
    public function __construct(private TontineService $tontines)
    {
    }

    /**
     * Mes tontines : uniquement celles où je suis membre actif.
     */
    public function index(Request $request): JsonResponse
    {
        $memberships = $request->user()->memberships()
            ->where('status', MemberStatus::Active->value)
            ->with(['tontine' => fn ($query) => $query
                ->withCount('activeMembers')
                ->with('manager:id,public_id,name')])
            ->orderByDesc('id')
            ->get();

        $data = $memberships
            ->map(fn (TontineMember $membership) => TontineResource::make($membership->tontine, $membership->role)->resolve())
            ->values();

        return response()->json(['data' => $data]);
    }

    /**
     * Création : l'autorisation (email vérifié) et la validation sont dans StoreTontineRequest.
     */
    public function store(StoreTontineRequest $request): JsonResponse
    {
        $tontine = $this->tontines->create($request->user(), $request->validated());

        $tontine->loadCount('activeMembers')->load('manager:id,public_id,name');

        return TontineResource::make($tontine, $this->tontines->roleOf($request->user(), $tontine))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Détail : réservé aux membres actifs (404 pour les autres).
     */
    public function show(Request $request, Tontine $tontine): TontineResource
    {
        Gate::authorize('view', $tontine);

        $tontine->loadCount('activeMembers')->load('manager:id,public_id,name');

        return TontineResource::make($tontine, $this->tontines->roleOf($request->user(), $tontine));
    }

    /**
     * Modification : réservée au gérant de CETTE tontine (contrôlée dans UpdateTontineRequest).
     */
    public function update(UpdateTontineRequest $request, Tontine $tontine): TontineResource
    {
        $tontine = $this->tontines->update($tontine, $request->user(), $request->validated());

        $tontine->loadCount('activeMembers')->load('manager:id,public_id,name');

        return TontineResource::make($tontine, $this->tontines->roleOf($request->user(), $tontine));
    }
}

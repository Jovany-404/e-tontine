<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContributionRequest;
use App\Http\Requests\ValidateContributionRequest;
use App\Http\Resources\ContributionResource;
use App\Services\ContributionService;
use App\Models\Contribution;
use App\Models\Membership;
use Illuminate\Http\JsonResponse;

class ContributionController extends Controller
{
    protected ContributionService $contributionService;

    public function __construct(ContributionService $contributionService)
    {
        $this->contributionService = $contributionService;
    }

    public function index(int $tontineId, string $statut = null): JsonResponse
    {
        $data = $this->contributionService->getByTontine($tontineId, $statut);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function store(StoreContributionRequest $request, int $membershipId): JsonResponse
    {
        $membership = Membership::find($membershipId);

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'Membership non trouvé',
            ], 404);
        }

        $data = $request->validated();

        $contribution = $this->contributionService->register($membership, $data);

        return response()->json([
            'success' => true,
            'message' => 'Contribution enregistrée avec succès',
            'data' => new ContributionResource($contribution),
        ], 201);
    }

    public function validate(ValidateContributionRequest $request, Contribution $contribution): JsonResponse
    {
        $data = $request->validated();

        $contribution = $this->contributionService->validate($contribution, $data);

        return response()->json([
            'success' => true,
            'message' => 'Contribution validée avec succès',
            'data' => new ContributionResource($contribution),
        ], 200);
    }

    public function show(Contribution $contribution): JsonResponse
    {
        $data = [
            'contribution' => $contribution->load(['membership.member', 'payments'])->toArray(),
            'paiements' => $this->contributionService->getByContribution($contribution),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
}

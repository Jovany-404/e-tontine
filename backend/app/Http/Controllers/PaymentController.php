<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\ValidatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use App\Models\Payment;
use App\Models\Contribution;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(int $tontineId): JsonResponse
    {
        $data = $this->paymentService->getByTontine($tontineId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function store(StorePaymentRequest $request, int $contributionId): JsonResponse
    {
        $contribution = Contribution::find($contributionId);

        if (!$contribution) {
            return response()->json([
                'success' => false,
                'message' => 'Contribution non trouvée',
            ], 404);
        }

        $data = $request->validated();

        $payment = $this->paymentService->create($contribution, $data);

        return response()->json([
            'success' => true,
            'message' => 'Paiement enregistré avec succès',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    public function validate(ValidatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $data = $request->validated();

        $payment = $this->paymentService->validate($payment, $data);

        return response()->json([
            'success' => true,
            'message' => 'Paiement validé avec succès',
            'data' => new PaymentResource($payment),
        ], 200);
    }

    public function show(Payment $payment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $payment->load(['contribution.membership.member', 'utilisateur'])->toArray(),
        ], 200);
    }
}

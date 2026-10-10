<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\ContributionScheduleResource;
use App\Http\Resources\PaymentResource;
use App\Models\ContributionSchedule;
use App\Models\Payment;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function index(Request $request, Tontine $tontine): JsonResponse
    {
        Gate::authorize('view', $tontine);

        $membership = TontineMember::query()
            ->where('tontine_id', $tontine->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();

        $payments = Payment::query()
            ->whereHas('schedule.cycle', fn ($query) => $query->where('tontine_id', $tontine->id))
            ->when(
                ! $membership->isManager(),
                fn ($query) => $query->whereHas('schedule', fn ($scheduleQuery) => $scheduleQuery
                    ->where('tontine_member_id', $membership->id)),
            )
            ->with([
                'schedule.member.user:id,public_id,name',
                'schedule.cycle.tontine:id,public_id,name',
            ])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();

        return PaymentResource::collection($payments)->response();
    }

    public function schedules(Request $request, Tontine $tontine): JsonResponse
    {
        Gate::authorize('view', $tontine);

        $membership = TontineMember::query()
            ->where('tontine_id', $tontine->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();

        $schedules = $tontine->schedules()
            ->when(
                ! $membership->isManager(),
                fn ($query) => $query->where('tontine_member_id', $membership->id),
            )
            ->with([
                'cycle:id,tontine_id,number',
                'member.user:id,public_id,name',
                'payments',
            ])
            ->orderBy('due_date')
            ->orderBy('round')
            ->get();

        return ContributionScheduleResource::collection($schedules)->response();
    }

    public function store(
        StorePaymentRequest $request,
        Tontine $tontine,
        ContributionSchedule $schedule,
    ): JsonResponse {
        $payment = $this->payments->record(
            $tontine,
            $schedule,
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'data' => [
                'payment' => PaymentResource::make($payment)->resolve(),
                'schedule' => ContributionScheduleResource::make($payment->schedule)->resolve(),
            ],
        ], 201);
    }
}

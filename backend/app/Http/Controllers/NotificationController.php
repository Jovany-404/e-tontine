<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $nonLis = $request->get('non_lis', false);

        $data = $this->notificationService->getByUser(auth()->id(), $nonLis);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $notification = $this->notificationService->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Notification créée avec succès',
            'data' => new NotificationResource($notification),
        ], 201);
    }

    public function read(int $notificationId): JsonResponse
    {
        $this->notificationService->markAsRead($notificationId);

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
        ], 200);
    }

    public function readAll(): JsonResponse
    {
        $this->notificationService->markAllAsRead(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications marquées comme lues',
        ], 200);
    }
}

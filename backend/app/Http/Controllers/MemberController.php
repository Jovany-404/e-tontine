<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Services\MemberService;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    protected MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = $request->get('query');

        if ($query) {
            $members = $this->memberService->search($query);
        } else {
            $members = Member::query()
                ->where('is_active', true)
                ->orderBy('nom')
                ->get()
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => $members,
        ], 200);
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $data = $request->validated();

        $member = $this->memberService->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Membre créé avec succès',
            'data' => new MemberResource($member),
        ], 201);
    }

    public function show(Member $member): JsonResponse
    {
        $data = $this->memberService->getWithMemberships($member);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function update(UpdateMemberRequest $request, Member $member): JsonResponse
    {
        $data = $request->validated();

        $member = $this->memberService->update($member, $data);

        return response()->json([
            'success' => true,
            'message' => 'Membre modifié avec succès',
            'data' => new MemberResource($member),
        ], 200);
    }

    public function delete(Member $member): JsonResponse
    {
        $this->memberService->delete($member);

        return response()->json([
            'success' => true,
            'message' => 'Membre supprimé avec succès',
        ], 200);
    }
}

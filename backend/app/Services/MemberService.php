<?php

namespace App\Services;

use App\Models\Member;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class MemberService
{
    public function create(array $data): Member
    {
        $member = Member::create($data);

        $this->logActivity('create', 'Membre créé', [
            'nom' => $member->nom,
            'prenom' =>
             $member->prenom,
        ]);

        return $member;
    }

    public function update(Member $member, array $data): Member
    {
        $avant = $member->toArray();

        $member->update($data);

        $this->logActivity('update', 'Membre modifié', $avant, $member->toArray());

        return $member;
    }

    public function delete(Member $member): void
    {
        $member->delete();

        $this->logActivity('delete', 'Membre supprimé', [
            'id' => $member->id,
            'nom' => $member->nom,
        ]);
    }

    public function search(string $query): array
    {
        return Member::query()
            ->where(function ($q) use ($query) {
                $q->where('nom', 'like', '%' . $query . '%')
                  ->orWhere('prenom', 'like', '%' . $query . '%')
                  ->orWhere('telephone', 'like', '%' . $query . '%')
                  ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->where('is_active', true)
            ->orderBy('nom')
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function getWithMemberships(Member $member): array
    {
        return [
            'member' => $member->toArray(),
            'membreships' => $member->memberships()->with('tontine')->get()->toArray(),
            'total_tontines' => $member->tontines()->count(),
        ];
    }

    private function logActivity(string $action, string $description, array $avant = null, array $apres = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entite' => 'Member',
            'description' => $description,
            'donnees_avant' => $avant,
            'donnees_apres' => $apres,
            'ip_address' => request()->ip(),
        ]);
    }
}

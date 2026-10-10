<?php

namespace App\Services;

use App\Models\Tontine;
use App\Models\Membership;
use App\Models\Contribution;
use App\Models\Schedule;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class TontineService
{
    public function create(array $data): Tontine
    {
        $tontine = Tontine::create([
            'user_id' => Auth::id(),
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null,
            'montant_total' => $data['montant_total'],
            'montant_membre' => $data['montant_membre'],
            'nombre_membres' => 0,
            'nombre_cycles' => $data['nombre_cycles'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'] ?? null,
            'statut' => 'en_attente',
            'type_rotation' => $data['type_rotation'] ?? 'chrono',
            'delai_paiement_jours' => $data['delai_paiement_jours'] ?? 5,
            'notification_active' => $data['notification_active'] ?? true,
        ]);

        $this->logActivity($tontine, 'create', 'Tontine créée', [
            'nom' => $tontine->nom,
            'montant_total' => $tontine->montant_total,
        ]);

        return $tontine;
    }

    public function update(Tontine $tontine, array $data): Tontine
    {
        $avant = $tontine->toArray();

        $tontine->update($data);

        $this->logActivity($tontine, 'update', 'Tontine modifiée', $avant, $tontine->toArray());

        return $tontine;
    }

    public function archive(Tontine $tontine): Tontine
    {
        $tontine->update(['statut' => 'archivee']);

        $this->logActivity($tontine, 'archive', 'Tontine archivée');

        return $tontine;
    }

    public function close(Tontine $tontine): Tontine
    {
        $tontine->update([
            'statut' => 'close',
            'date_fin' => now(),
        ]);

        // Marquer tous les schedules comme réalisés
        $tontine->schedules()->update(['statut' => 'realise']);

        $this->logActivity($tontine, 'close', 'Tontine close');

        return $tontine;
    }

    public function addMember(Tontine $tontine, array $data): Membership
    {
        $membership = Membership::create([
            'tontine_id' => $tontine->id,
            'member_id' => $data['member_id'],
            'position' => $data['position'] ?? null,
            'montant_engage' => $tontine->montant_membre,
            'montant_paye' => 0,
            'montant_restant' => $tontine->montant_membre,
            'statut' => 'actif',
            'date_inscription' => now(),
        ]);

        $tontine->update(['nombre_membres' => $tontine->nombre_membres + 1]);

        $this->logActivity($tontine, 'create', 'Membre ajouté', [
            'member_id' => $membership->member_id,
            'nom' => $data['member_nom'],
        ]);

        return $membership;
    }

    public function removeMember(Tontine $tontine, Membership $membership): void
    {
        $membership->update(['statut' => 'exclu']);
        $tontine->update(['nombre_membres' => $tontine->nombre_membres - 1]);

        $this->logActivity($tontine, 'delete', 'Membre supprimé', [
            'member_id' => $membership->member_id,
        ]);
    }

    public function getStatistics(Tontine $tontine): array
    {
        $membresActifs = $tontine->memberships()->where('statut', 'actif')->count();
        $membresPaye = $tontine->nombre_membres_paye;
        $membresRetard = $tontine->nombre_membres_retard;
        $totalPaye = $tontine->montant_total_paye;
        $totalRestant = $tontine->montant_total_restant;
        $progression = $tontine->progression;

        return [
            'total_membres' => $tontine->nombre_membres,
            'membres_actifs' => $membresActifs,
            'membres_paye' => $membresPaye,
            'membres_retard' => $membresRetard,
            'total_colle' => $totalPaye,
            'total_expected' => $tontine->montant_total,
            'total_restant' => $totalRestant,
            'progression' => $progression,
            'statut' => $tontine->statut,
            'date_debut' => $tontine->date_debut,
            'date_fin' => $tontine->date_fin,
        ];
    }

    public function generateSchedules(Tontine $tontine): void
    {
        $membres = $tontine->memberships()
            ->where('statut', 'actif')
            ->orderBy('position')
            ->get();

        if ($membres->isEmpty()) return;

        $dateBene = $tontine->date_debut;
        $intervalle = $tontine->nombre_cycles > 0 
            ? $tontine->date_fin->diffInDays($tontine->date_debut) / $tontine->nombre_cycles 
            : 30;

        foreach ($membres as $index => $membership) {
            Schedule::create([
                'tontine_id' => $tontine->id,
                'position_beneficiaire' => $index + 1,
                'member_id' => $membership->member_id,
                'date_benefice' => $dateBene->addDays($intervalle),
                'montant_benefice' => $tontine->montant_total,
                'statut' => 'prevu',
            ]);
        }
    }

    private function logActivity(Tontine $tontine, string $action, string $description, array $avant = null, array $apres = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'tontine_id' => $tontine->id,
            'action' => $action,
            'entite' => 'Tontine',
            'entite_id' => $tontine->id,
            'description' => $description,
            'donnees_avant' => $avant,
            'donnees_apres' => $apres,
            'ip_address' => request()->ip(),
        ]);
    }
}
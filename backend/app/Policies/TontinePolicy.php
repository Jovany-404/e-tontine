<?php

namespace App\Policies;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Permissions liées à UNE tontine.
 *
 * Règles de base :
 * - il n'existe aucun droit global : tout se calcule à partir de l'appartenance à cette tontine ;
 * - un non-membre (ou un membre inactif) reçoit un 404, pour ne pas révéler que la tontine existe ;
 * - un membre actif sans le droit demandé reçoit un 403.
 */
class TontinePolicy
{
    /** Créer une tontine : réservé aux comptes dont l'email est vérifié. */
    public function create(User $user): Response
    {
        return $user->hasVerifiedEmail()
            ? Response::allow()
            : Response::deny(__('Your email address is not verified.'));
    }

    /** Voir la tontine : tout membre actif (gérant inclus). */
    public function view(User $user, Tontine $tontine): Response
    {
        return $this->asMember($user, $tontine);
    }

    /** Voir la liste des membres (informations minimales, filtrées par la Resource). */
    public function viewMembers(User $user, Tontine $tontine): Response
    {
        return $this->asMember($user, $tontine);
    }

    /** Modifier la configuration de la tontine. */
    public function update(User $user, Tontine $tontine): Response
    {
        return $this->asManager($user, $tontine);
    }

    /** Générer, désactiver ou régénérer le lien d'invitation. */
    public function manageInvitations(User $user, Tontine $tontine): Response
    {
        return $this->asManager($user, $tontine);
    }

    /** Voir, accepter ou refuser les demandes d'adhésion. */
    public function decideMembershipRequests(User $user, Tontine $tontine): Response
    {
        return $this->asManager($user, $tontine);
    }

    /** Enregistrer ou corriger un paiement. */
    public function recordPayments(User $user, Tontine $tontine): Response
    {
        return $this->asManager($user, $tontine);
    }

    /** Voir les paiements de tous les membres (un membre ne voit que les siens). */
    public function viewAllPayments(User $user, Tontine $tontine): Response
    {
        return $this->asManager($user, $tontine);
    }

    /** Lancer le tirage. */
    public function draw(User $user, Tontine $tontine): Response
    {
        return $this->asManager($user, $tontine);
    }

    /** Consulter le journal d'activité de la tontine. */
    public function viewActivity(User $user, Tontine $tontine): Response
    {
        return $this->asManager($user, $tontine);
    }

    // ---------- Méthodes internes ----------

    /** Autorise tout membre actif ; sinon 404. */
    private function asMember(User $user, Tontine $tontine): Response
    {
        return $this->activeMembership($user, $tontine) !== null
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** Autorise uniquement le gérant actif de cette tontine ; 404 pour un étranger, 403 pour un simple membre. */
    private function asManager(User $user, Tontine $tontine): Response
    {
        $membership = $this->activeMembership($user, $tontine);

        if ($membership === null) {
            return Response::denyAsNotFound();
        }

        return $membership->role === MemberRole::Manager
            ? Response::allow()
            : Response::deny(__('Only the manager of this tontine can do this.'));
    }

    /** Appartenance active de l'utilisateur à CETTE tontine (null s'il n'en fait pas partie). */
    private function activeMembership(User $user, Tontine $tontine): ?TontineMember
    {
        return TontineMember::query()
            ->where('tontine_id', $tontine->id)
            ->where('user_id', $user->id)
            ->where('status', MemberStatus::Active->value)
            ->first();
    }
}

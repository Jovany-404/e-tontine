<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Contribution;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class PaymentService
{
    public function create(Contribution $contribution, array $data): Payment
    {
        $reference = $data['reference'] ?? 'PAY-' . strtoupper(substr($contribution->id . '-' . now()->timestamp, 0, 12));

        $payment = Payment::create([
            'contribution_id' => $contribution->id,
            'reference' => $reference,
            'methode' => $data['methode'],
            'organisme' => $data['organisme'] ?? null,
            'montant' => $data['montant'],
            'date_paiement' => $data['date_paiement'],
            'chemin_piece_jointe' => $data['chemin_piece_jointe'] ?? null,
            'commentaire' => $data['commentaire'] ?? null,
        ]);

        $this->logActivity($contribution->tontine_id, 'payement', 'Paiement enregistré', [
            'reference' => $payment->reference,
            'montant' => $payment->montant,
        ]);

        return $payment;
    }

    public function validate(Payment $payment, array $data): Payment
    {
        $avant = $payment->toArray();

        $payment->update([
            'validated_by' => Auth::id(),
            'date_validation' => now(),
        ]);

        // Validate contribution
        $payment->contribution->update(['statut' => 'paye']);

        $this->logActivity($payment->contribution->tontine_id, 'validation', 'Paiement validé', $avant, $payment->toArray());

        return $payment;
    }

    public function getByContribution(Contribution $contribution): array
    {
        $payments = $contribution->payments()->orderBy('date_paiement', 'desc')->get();

        return [
            'payments' => $payments->toArray(),
            'total' => $payments->sum('montant'),
            'count' => $payments->count(),
        ];
    }

    public function getByTontine(int $tontineId): array
    {
        $payments = Payment::query()
            ->whereHas('contribution', function ($q) use ($tontineId) {
                $q->where('tontine_id', $tontineId);
            })
            ->orderBy('date_paiement', 'desc')
            ->get();

        return [
            'payments' => $payments->toArray(),
            'total' => $payments->sum('montant'),
            'count' => $payments->count(),
        ];
    }

    private function logActivity(int $tontineId, string $action, string $description, array $avant = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'tontine_id' => $tontineId,
            'action' => $action,
            'entite' => 'Payment',
            'description' => $description,
            'donnees_avant' => $avant,
            'ip_address' => request()->ip(),
        ]);
    }
}

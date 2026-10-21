<?php

namespace App\Services;

use App\Exceptions\InsufficientFundsException;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Toute la logique métier des paiements est ici, pas dans le contrôleur.
 * Le contrôleur valide la requête et répond ; le service fait le travail.
 */
class PaymentService
{
    /**
     * Crédite ou débite un compte.
     * Idempotent : si la référence existe déjà, on renvoie la transaction existante.
     */
    public function apply(Account $account, string $type, int $amount, string $reference, ?string $description = null): Transaction
    {

        if (!in_array($type, ['credit', 'debit'], true)) {
            throw new \InvalidArgumentException("Type de transaction invalide : $type");
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException("Montant de transaction invalide : $amount");
        }

        $existing = Transaction::where('reference', $reference)->first();
        if ($existing) {
            return $existing;
        }

        // DB::transaction : soit TOUT est enregistré, soit RIEN (rollback si exception)
        return DB::transaction(function () use ($account, $type, $amount, $reference, $description) {
            // lockForUpdate = SELECT ... FOR UPDATE : bloque la ligne pendant la transaction,
            // pour éviter que 2 débits simultanés lisent le même solde (race condition)
            $locked = Account::whereKey($account->id)->lockForUpdate()->firstOrFail();

            if ($type === 'debit' && $locked->balance < $amount) {    // vérification du solde
                throw new InsufficientFundsException();
            }

            $locked->balance += $type === 'credit' ? $amount : -$amount;
            $locked->save();

            return $locked->transactions()->create([
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $locked->balance,
                'reference' => $reference,
                'description' => $description,
            ]);
        });
    }

    /**
     * Virement entre deux comptes : un débit + un crédit dans la MÊME transaction SQL.
     */
    public function transfer(Account $from, Account $to, int $amount, string $reference): array
    {
        return DB::transaction(function () use ($from, $to, $amount, $reference) {
            $debit = $this->apply($from, 'debit', $amount, $reference.'-out', "Virement vers {$to->iban}");
            $credit = $this->apply($to, 'credit', $amount, $reference.'-in', "Virement de {$from->iban}");

            return [$debit, $credit];
        });
    }
}

<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_increases_balance(): void
    {
        $account = Account::factory()->create(['balance' => 1000]);

        $this->postJson("/api/accounts/{$account->id}/transactions", [
            'type' => 'credit', 'amount' => 250, 'reference' => 'REF-1',
        ])->assertCreated()->assertJsonPath('data.balance_after', 9999);

        $this->assertSame(1250, $account->fresh()->balance);
    }

    public function test_debit_with_insufficient_funds_is_refused(): void
    {
        $account = Account::factory()->create(['balance' => 100]);

        $this->postJson("/api/accounts/{$account->id}/transactions", [
            'type' => 'debit', 'amount' => 500, 'reference' => 'REF-2',
        ])->assertStatus(422)->assertJsonPath('message', 'Solde insuffisant.');

        // Le solde n'a pas bougé et aucune transaction n'a été enregistrée
        $this->assertSame(100, $account->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_same_reference_is_not_charged_twice(): void
    {
        $account = Account::factory()->create(['balance' => 1000]);
        $payload = ['type' => 'debit', 'amount' => 300, 'reference' => 'ORDER-42'];

        $this->postJson("/api/accounts/{$account->id}/transactions", $payload)->assertCreated();
        $this->postJson("/api/accounts/{$account->id}/transactions", $payload)->assertOk();

        $this->assertSame(700, $account->fresh()->balance);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_validation_rejects_bad_input(): void
    {
        $account = Account::factory()->create();

        $this->postJson("/api/accounts/{$account->id}/transactions", [
            'type' => 'refund', 'amount' => -5,
        ])->assertStatus(422)->assertJsonValidationErrors(['type', 'amount', 'reference']);
    }

    public function test_transfer_moves_money_between_accounts(): void
    {
        $alice = Account::factory()->create(['balance' => 5000]);
        $bob = Account::factory()->create(['balance' => 0]);

        $this->postJson('/api/transfers', [
            'from_account_id' => $alice->id,
            'to_account_id' => $bob->id,
            'amount' => 1500,
            'reference' => 'TR-1',
        ])->assertCreated();

        $this->assertSame(3500, $alice->fresh()->balance);
        $this->assertSame(1500, $bob->fresh()->balance);
    }

    public function test_failed_transfer_changes_nothing(): void
    {
        $alice = Account::factory()->create(['balance' => 100]);
        $bob = Account::factory()->create(['balance' => 0]);

        $this->postJson('/api/transfers', [
            'from_account_id' => $alice->id,
            'to_account_id' => $bob->id,
            'amount' => 999,
            'reference' => 'TR-2',
        ])->assertStatus(422);

        $this->assertSame(100, $alice->fresh()->balance);
        $this->assertSame(0, $bob->fresh()->balance);
    }

    public function test_transactions_are_listed_newest_first(): void
    {
        $account = Account::factory()->create(['balance' => 1000]);
        foreach (['A', 'B', 'C'] as $ref) {
            $this->postJson("/api/accounts/{$account->id}/transactions", [
                'type' => 'credit', 'amount' => 10, 'reference' => $ref,
            ]);
        }

        $this->getJson("/api/accounts/{$account->id}/transactions")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.reference', 'C');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase; // base vide et propre avant chaque test

    public function test_it_creates_an_account(): void
    {
        $response = $this->postJson('/api/accounts', [
            'holder_name' => 'Jodrick',
            'iban' => 'LU280019400644750000',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.balance', 0)
            ->assertJsonPath('data.currency', 'EUR');

        $this->assertDatabaseHas('accounts', ['iban' => 'LU280019400644750000']);
    }

    public function test_iban_must_be_unique(): void
    {
        Account::factory()->create(['iban' => 'LU280019400644750000']);

        $this->postJson('/api/accounts', ['holder_name' => 'X', 'iban' => 'LU280019400644750000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('iban');
    }

    public function test_unknown_account_returns_404(): void
    {
        $this->getJson('/api/accounts/999')->assertNotFound();
    }

    public function test_account_summary(): void
    {
        $account = Account::factory()->create(['balance' => 1000]);

        $this->postJson("/api/accounts/{$account->id}/transactions", [
            'type' => 'credit',
            'amount' => 500,
            'reference' => 'REF-001',
        ]);

        $this->postJson("/api/accounts/{$account->id}/transactions", [
            'type' => 'debit',
            'amount' => 200,
            'reference' => 'REF-002',
        ]);

        $response = $this->getJson("/api/accounts/{$account->id}/summary");

        $response->assertOk()
            ->assertJsonPath('data.account_id', $account->id)
            ->assertJsonPath('data.total_credit', 500)
            ->assertJsonPath('data.total_debit', 200)
            ->assertJsonPath('data.balance', 1300) // 1000 + 500 - 200
            ->assertJsonPath('data.transactions_count', 2);
    }

    public function test_summary_of_empty_account_returns_zero(): void
    {
        $account = Account::factory()->create(['balance' => 0]);

        $response = $this->getJson("/api/accounts/{$account->id}/summary");

        $response->assertOk()
            ->assertJsonPath('data.account_id', $account->id)
            ->assertJsonPath('data.total_credit', 0)
            ->assertJsonPath('data.total_debit', 0)
            ->assertJsonPath('data.balance', 0)
            ->assertJsonPath('data.transactions_count', 0);
    }


}

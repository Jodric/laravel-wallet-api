<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    // Injection de dépendance : Laravel crée PaymentService pour nous
    public function __construct(private PaymentService $payments) {}

    // GET /api/accounts/{account}/transactions
    public function index(Account $account)
    {
        return TransactionResource::collection(
            $account->transactions()->latest('id')->paginate(20)
        );
    }

    // POST /api/accounts/{account}/transactions
    public function store(StoreTransactionRequest $request, Account $account)
    {
        $data = $request->validated();

        $tx = $this->payments->apply(
            $account, $data['type'], $data['amount'], $data['reference'], $data['description'] ?? null
        );

        // 201 si créée maintenant, 200 si la référence existait déjà (idempotence)
        return (new TransactionResource($tx))
            ->response()
            ->setStatusCode($tx->wasRecentlyCreated ? 201 : 200);
    }

    // POST /api/transfers
    public function transfer(Request $request)
    {
        $data = $request->validate([
            'from_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'to_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['required', 'string', 'max:60'],
        ]);

        [$debit, $credit] = $this->payments->transfer(
            Account::findOrFail($data['from_account_id']),
            Account::findOrFail($data['to_account_id']),
            $data['amount'],
            $data['reference'],
        );

        return response()->json([
            'debit' => new TransactionResource($debit),
            'credit' => new TransactionResource($credit),
        ], 201);
    }

}


  

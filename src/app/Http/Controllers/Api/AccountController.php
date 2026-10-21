<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    // GET /api/accounts
    public function index()
    {
        return AccountResource::collection(Account::orderBy('id')->paginate(20));
    }

    // POST /api/accounts
    public function store(Request $request)
    {
        $data = $request->validate([
            'holder_name' => ['required', 'string', 'max:255'],
            'iban' => ['required', 'string', 'max:34', 'unique:accounts,iban'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $account = Account::create($data);

        return (new AccountResource($account))->response()->setStatusCode(201);
    }

    // GET /api/accounts/{account}  (route model binding : Laravel trouve le compte ou renvoie 404)
    public function show(Account $account)
    {
        return new AccountResource($account);
    }

      // GET /api/accounts/{account}/summary
    public function summary(Account $account){
        
        $summary = $account->transactions()
            ->selectRaw('type, SUM(amount) as total_amount')
            ->groupBy('type')
            ->get()
            ->pluck('total_amount', 'type');

        return response()->json([
           'data' => [
                'account_id' => $account->id,
                'total_credit' => (int)$summary->get('credit', 0),
                'total_debit' => (int)$summary->get('debit', 0),
                'balance' =>  (int)$account->balance,
                "transactions_count" => $account->transactions()->count(),
            ]
        ]);
    }
    


}

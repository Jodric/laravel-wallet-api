<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // pas d'authentification dans ce mini-projet
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'integer', 'min:1'],          // en centimes
            'reference' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

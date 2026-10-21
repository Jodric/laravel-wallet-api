<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'holder_name' => $this->holder_name,
            'iban' => $this->iban,
            'currency' => $this->currency,
            'balance' => $this->balance,
            'balance_formatted' => number_format($this->balance / 100, 2, ',', ' ').' '.$this->currency,
        ];
    }
}

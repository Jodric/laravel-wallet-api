<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientFundsException extends Exception
{
    // Cas métier normal, pas un bug : on ne l'écrit pas dans les logs d'erreur
    public function report(): void {}

    // Laravel appelle render() automatiquement : on renvoie une erreur 422 propre en JSON
    public function render(): JsonResponse
    {
        return response()->json(['message' => 'Solde insuffisant.'], 422);
    }
}

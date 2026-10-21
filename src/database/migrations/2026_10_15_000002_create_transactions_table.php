<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            // Clé étrangère : chaque transaction appartient à un compte
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit']);
            $table->bigInteger('amount');            // en centimes, toujours positif
            $table->bigInteger('balance_after');     // solde après l'opération (traçabilité)
            // Référence unique = clé d'idempotence : la même requête envoyée 2 fois
            // ne crée pas 2 paiements
            $table->string('reference', 64)->unique();
            $table->string('description')->nullable();
            $table->timestamps();

            // Index composé : accélère "les transactions d'un compte, triées par date"
            $table->index(['account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

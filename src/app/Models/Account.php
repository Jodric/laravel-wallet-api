<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    // Champs qu'on peut remplir via create()/fill() (protection "mass assignment")
    protected $fillable = ['holder_name', 'iban', 'currency', 'balance'];

    // Valeurs par défaut côté PHP (sinon balance vaut null juste après create())
    protected $attributes = ['balance' => 0, 'currency' => 'EUR'];

    protected function casts(): array
    {
        return ['balance' => 'integer'];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}

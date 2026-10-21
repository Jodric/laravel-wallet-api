<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'holder_name' => fake()->name(),
            'iban' => 'LU'.fake()->unique()->numerify('##################'),
            'currency' => 'EUR',
            'balance' => 10000, // 100,00 EUR
        ];
    }
}

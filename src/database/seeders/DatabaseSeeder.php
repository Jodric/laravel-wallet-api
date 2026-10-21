<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Account::factory()->create(['holder_name' => 'Alice Martin', 'balance' => 50000]);
        Account::factory()->create(['holder_name' => 'Bob Schmit', 'balance' => 2000]);
    }
}

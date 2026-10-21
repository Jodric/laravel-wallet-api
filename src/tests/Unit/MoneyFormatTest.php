<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// Test UNITAIRE : teste une petite fonction isolée, sans base de données ni HTTP
class MoneyFormatTest extends TestCase
{
    public function test_cents_are_formatted_in_euros(): void
    {
        $this->assertSame('1 234,50', number_format(123450 / 100, 2, ',', ' '));
    }

    public function test_float_is_dangerous_for_money(): void
    {
        // Pourquoi on stocke en centimes : 0.1 + 0.2 n'est PAS égal à 0.3 en float
        $this->assertNotSame(0.3, 0.1 + 0.2);
        $this->assertSame(30, 10 + 20); // en centimes, aucun problème
    }
}

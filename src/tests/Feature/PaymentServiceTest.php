<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_refuses_negative_amount(): void
    {
        $account = Account::factory()->create(['balance' => 1000]);
        $service = app(PaymentService::class);   // Laravel crée le service pour nous

        // TODO 1 : dire à PHPUnit qu'on S'ATTEND à une InvalidArgumentException
        //          (cherche "expectException" sur docs.phpunit.de)

       $this->expectException(\InvalidArgumentException::class);

        // TODO 2 : appeler $service->apply(...) avec un crédit de -500
       $service->apply($account, 'credit', -500, 'REF-NEGATIVE');
    }
}
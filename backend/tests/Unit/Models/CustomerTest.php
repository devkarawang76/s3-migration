<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    public function test_customer_model_configuration(): void
    {
        $customer = new Customer();
        $this->assertEquals('customers', $customer->getTable());
        $this->assertEquals('id', $customer->getKeyName());
    }
}
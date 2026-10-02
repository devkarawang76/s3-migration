<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use App\Models\Unit;
use App\Models\Customer;
use App\Models\Engineer;
use App\Models\ServiceOffice;
use Tests\TestCase;

class CoreModelTest extends TestCase
{
    public function test_ticket_model_uses_proper_table_and_primary_key(): void
    {
        $ticket = new Ticket();
        $this->assertEquals('tickets', $ticket->getTable());
        $this->assertEquals('id', $ticket->getKeyName());
        $this->assertTrue($ticket->getIncrementing());
    }

    public function test_unit_model_uses_proper_table(): void
    {
        $unit = new Unit();
        $this->assertEquals('units', $unit->getTable());
        $this->assertEquals('id', $unit->getKeyName());
    }

    public function test_customer_model_uses_proper_table(): void
    {
        $customer = new Customer();
        $this->assertEquals('customers', $customer->getTable());
    }

    public function test_engineer_model_uses_proper_table(): void
    {
        $engineer = new Engineer();
        $this->assertEquals('engineers', $engineer->getTable());
    }

    public function test_service_office_model_uses_proper_table(): void
    {
        $serviceOffice = new ServiceOffice();
        $this->assertEquals('service_offices', $serviceOffice->getTable());
    }
}
<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use Tests\TestCase;

class TicketTest extends TestCase
{
    public function test_ticket_model_configuration(): void
    {
        $ticket = new Ticket();
        $this->assertEquals('tickets', $ticket->getTable());
        $this->assertEquals('id', $ticket->getKeyName());
        $this->assertTrue($ticket->getIncrementing());
    }
}
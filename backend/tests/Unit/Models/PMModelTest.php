<?php

namespace Tests\Unit\Models;

use App\Models\PreventiveMaintenance;
use App\Models\PmTicketLink;
use Tests\TestCase;

class PMModelTest extends TestCase
{
    public function test_preventive_maintenance_model_uses_proper_table(): void
    {
        $pm = new PreventiveMaintenance();
        $this->assertEquals('preventive_maintenances', $pm->getTable());
        $this->assertEquals('id', $pm->getKeyName());
    }

    public function test_pm_ticket_link_model_uses_proper_table(): void
    {
        $link = new PmTicketLink();
        $this->assertEquals('pm_ticket_links', $link->getTable());
        $this->assertEquals('id', $link->getKeyName());
    }
}
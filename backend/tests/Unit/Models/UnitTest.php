<?php

namespace Tests\Unit\Models;

use App\Models\Unit;
use Tests\TestCase;

class UnitTest extends TestCase
{
    public function test_unit_model_configuration(): void
    {
        $unit = new Unit();
        $this->assertEquals('units', $unit->getTable());
        $this->assertEquals('id', $unit->getKeyName());
    }
}
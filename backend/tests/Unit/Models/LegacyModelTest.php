<?php

namespace Tests\Unit\Models;

use App\Models\LegacyModel;
use Tests\TestCase;

class LegacyModelTest extends TestCase
{
    public function test_legacy_model_has_timestamps_disabled(): void
    {
        $model = new class extends LegacyModel {
            protected $table = 'test_table';
        };

        $this->assertFalse($model->timestamps);
    }

    public function test_legacy_model_has_guarded_empty(): void
    {
        $model = new class extends LegacyModel {
            protected $table = 'test_table';
        };

        $this->assertEquals([], $model->getGuarded());
    }
}

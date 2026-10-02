<?php

namespace Tests\Unit\Models;

use App\Models\TicketComment;
use App\Models\TicketTimeline;
use App\Models\TicketUnit;
use App\Models\TicketHistoryStatus;
use App\Models\TicketRequestPart;
use App\Models\TicketAssetList;
use App\Models\WarrantyUnit;
use App\Models\UnitType;
use App\Models\ModelType;
use App\Models\WarehouseList;
use App\Models\SupplierList;
use App\Models\StockLogistic;
use App\Models\ProductList;
use App\Models\CompPart;
use App\Models\TicketStatus;
use App\Models\CustomerList;
use App\Models\UnitCustomer;
use App\Models\EnumMapping;
use Tests\TestCase;

class SupportingModelTest extends TestCase
{
    public function test_basic_supporting_models_exist(): void
    {
        $models = [
            TicketComment::class,
            TicketTimeline::class,
            TicketUnit::class,
            TicketHistoryStatus::class,
            TicketRequestPart::class,
            TicketAssetList::class,
            WarrantyUnit::class,
            UnitType::class,
            ModelType::class,
            WarehouseList::class,
            SupplierList::class,
            StockLogistic::class,
            ProductList::class,
            CompPart::class,
            TicketStatus::class,
            CustomerList::class,
            UnitCustomer::class,
            EnumMapping::class,
        ];

        foreach ($models as $model) {
            $instance = new $model();
            $this->assertInstanceOf($model, $instance);
        }
    }
}
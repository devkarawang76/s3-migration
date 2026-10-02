<?php

namespace App\Models;

class WarehouseList extends LegacyModel
{
    protected $table = 'warehouse_lists';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

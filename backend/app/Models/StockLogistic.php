<?php

namespace App\Models;

class StockLogistic extends LegacyModel
{
    protected $table = 'stock_logistics';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

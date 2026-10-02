<?php

namespace App\Models;

class SalesOrderDetail extends LegacyModel
{
    protected $table = 'sales_order_details';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

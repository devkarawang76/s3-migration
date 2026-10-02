<?php

namespace App\Models;

class SalesOrderList extends LegacyModel
{
    protected $table = 'sales_order_lists';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

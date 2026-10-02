<?php

namespace App\Models;

class SalesOrderApproval extends LegacyModel
{
    protected $table = 'sales_order_approvals';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

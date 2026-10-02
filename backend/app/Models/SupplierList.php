<?php

namespace App\Models;

class SupplierList extends LegacyModel
{
    protected $table = 'supplier_lists';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

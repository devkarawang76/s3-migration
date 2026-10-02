<?php

namespace App\Models;

class UnitCustomer extends LegacyModel
{
    protected $table = 'unit_customers';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

<?php

namespace App\Models;

class WarrantyUnit extends LegacyModel
{
    protected $table = 'warranty_units';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

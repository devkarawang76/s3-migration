<?php

namespace App\Models;

class UnitType extends LegacyModel
{
    protected $table = 'unit_types';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

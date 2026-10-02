<?php

namespace App\Models;

class EnumMapping extends LegacyModel
{
    protected $table = 'enum_mappings';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

<?php

namespace App\Models;

class Unit extends LegacyModel
{
    protected $table = 'units';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}
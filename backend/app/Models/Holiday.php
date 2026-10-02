<?php

namespace App\Models;

class Holiday extends LegacyModel
{
    protected $table = 'holidays';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

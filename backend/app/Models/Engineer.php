<?php

namespace App\Models;

class Engineer extends LegacyModel
{
    protected $table = 'engineers';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}
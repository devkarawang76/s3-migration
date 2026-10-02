<?php

namespace App\Models;

class SprfList extends LegacyModel
{
    protected $table = 'sprf_lists';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

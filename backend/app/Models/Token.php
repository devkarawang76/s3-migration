<?php

namespace App\Models;

class Token extends LegacyModel
{
    protected $table = 'tokens';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

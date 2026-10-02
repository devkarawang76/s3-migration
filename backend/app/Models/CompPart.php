<?php

namespace App\Models;

class CompPart extends LegacyModel
{
    protected $table = 'comp_parts';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

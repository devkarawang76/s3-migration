<?php

namespace App\Models;

class InternalLog extends LegacyModel
{
    protected $table = 'internal_logs';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

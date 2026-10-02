<?php

namespace App\Models;

class CallLog extends LegacyModel
{
    protected $table = 'call_logs';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

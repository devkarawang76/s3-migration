<?php

namespace App\Models;

class ServiceOffice extends LegacyModel
{
    protected $table = 'service_offices';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}
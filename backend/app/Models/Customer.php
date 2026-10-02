<?php

namespace App\Models;

class Customer extends LegacyModel
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}
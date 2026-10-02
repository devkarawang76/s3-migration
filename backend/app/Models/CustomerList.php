<?php

namespace App\Models;

class CustomerList extends LegacyModel
{
    protected $table = 'customer_lists';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

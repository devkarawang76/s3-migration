<?php

namespace App\Models;

class ProductList extends LegacyModel
{
    protected $table = 'product_lists';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

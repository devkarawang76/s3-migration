<?php

namespace App\Models;

class QuotationList extends LegacyModel
{
    protected $table = 'quotation_lists';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

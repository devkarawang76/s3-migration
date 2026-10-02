<?php

namespace App\Models;

class QuotationDetail extends LegacyModel
{
    protected $table = 'quotation_details';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

<?php

namespace App\Models;

class QuotationApproval extends LegacyModel
{
    protected $table = 'quotation_approvals';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

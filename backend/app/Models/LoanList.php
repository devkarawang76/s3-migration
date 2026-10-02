<?php

namespace App\Models;

class LoanList extends LegacyModel
{
    protected $table = 'loan_lists';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

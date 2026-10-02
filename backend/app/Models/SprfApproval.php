<?php

namespace App\Models;

class SprfApproval extends LegacyModel
{
    protected $table = 'sprf_approvals';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

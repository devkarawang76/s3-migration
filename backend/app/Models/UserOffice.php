<?php

namespace App\Models;

class UserOffice extends LegacyModel
{
    protected $table = 'user_offices';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

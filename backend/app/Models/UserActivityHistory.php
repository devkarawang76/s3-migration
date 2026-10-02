<?php

namespace App\Models;

class UserActivityHistory extends LegacyModel
{
    protected $table = 'user_activity_histories';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

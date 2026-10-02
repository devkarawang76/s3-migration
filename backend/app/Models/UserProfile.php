<?php

namespace App\Models;

class UserProfile extends LegacyModel
{
    protected $table = 'user_profiles';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

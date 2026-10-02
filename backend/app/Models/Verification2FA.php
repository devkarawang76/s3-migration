<?php

namespace App\Models;

class Verification2FA extends LegacyModel
{
    protected $table = 'verification2fa';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

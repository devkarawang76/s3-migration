<?php

namespace App\Models;

class Notification extends LegacyModel
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

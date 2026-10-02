<?php

namespace App\Models;

class Ticket extends LegacyModel
{
    protected $table = 'tickets';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}
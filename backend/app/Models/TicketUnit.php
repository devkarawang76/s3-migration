<?php

namespace App\Models;

class TicketUnit extends LegacyModel
{
    protected $table = 'ticket_units';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

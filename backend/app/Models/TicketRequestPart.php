<?php

namespace App\Models;

class TicketRequestPart extends LegacyModel
{
    protected $table = 'ticket_request_parts';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

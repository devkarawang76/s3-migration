<?php

namespace App\Models;

class TicketStatus extends LegacyModel
{
    protected $table = 'ticket_statuses';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

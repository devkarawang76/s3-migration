<?php

namespace App\Models;

class TicketHistoryRequestPart extends LegacyModel
{
    protected $table = 'ticket_history_request_parts';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

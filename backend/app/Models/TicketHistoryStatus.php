<?php

namespace App\Models;

class TicketHistoryStatus extends LegacyModel
{
    protected $table = 'ticket_history_statuses';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

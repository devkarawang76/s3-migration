<?php

namespace App\Models;

class TicketTimeline extends LegacyModel
{
    protected $table = 'ticket_timelines';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

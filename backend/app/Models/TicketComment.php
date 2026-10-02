<?php

namespace App\Models;

class TicketComment extends LegacyModel
{
    protected $table = 'ticket_comments';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

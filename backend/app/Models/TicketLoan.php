<?php

namespace App\Models;

class TicketLoan extends LegacyModel
{
    protected $table = 'ticket_loans';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

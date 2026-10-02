<?php

namespace App\Models;

class PmTicketLink extends LegacyModel
{
    protected $table = 'pm_ticket_links';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}
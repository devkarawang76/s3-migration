<?php

namespace App\Models;

class TicketAssetList extends LegacyModel
{
    protected $table = 'ticket_asset_lists';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

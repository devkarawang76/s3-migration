<?php

namespace App\Models;

class AssetApprovalList extends LegacyModel
{
    protected $table = 'asset_approval_lists';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

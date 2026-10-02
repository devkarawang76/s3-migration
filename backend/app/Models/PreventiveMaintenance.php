<?php

namespace App\Models;

class PreventiveMaintenance extends LegacyModel
{
    protected $table = 'preventive_maintenances';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}
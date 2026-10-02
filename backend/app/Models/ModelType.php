<?php

namespace App\Models;

class ModelType extends LegacyModel
{
    protected $table = 'model_types';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

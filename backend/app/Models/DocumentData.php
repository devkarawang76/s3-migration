<?php

namespace App\Models;

class DocumentData extends LegacyModel
{
    protected $table = 'document_data';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}

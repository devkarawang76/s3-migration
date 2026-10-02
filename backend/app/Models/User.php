<?php

namespace App\Models;

class User extends LegacyModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'legacy_user_id', 'username', 'email', 'password', 'name',
        'role', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];
}

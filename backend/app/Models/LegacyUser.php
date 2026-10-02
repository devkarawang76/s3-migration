<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;

class LegacyUser extends LegacyModel implements Authenticatable
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'username', 'password', 'name', 'email', 'role',
        'blokir', 'photo', 'office_id', 'level_user', 'eng_id',
        'rule_user', 'user_language', 'created_at', 'updated_at',
    ];

    protected $hidden = ['password'];

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    public function getAuthIdentifier(): string
    {
        return $this->username;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): ?string
    {
        return $this->remember_token ?? null;
    }

    public function setRememberToken($value): void
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}

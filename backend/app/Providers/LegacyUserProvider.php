<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;
use App\Models\LegacyUser;

class LegacyUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return LegacyUser::where('user_id', $identifier)->first();
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return LegacyUser::where('user_id', $identifier)
            ->where('remember_token', $token)
            ->first();
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->setRememberToken($token);
        $user->save();
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials['username'])) {
            return null;
        }

        return LegacyUser::where('username', $credentials['username'])
            ->where('blokir', 'N')
            ->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials['password'];
        $hash = $user->getAuthPassword();

        // Check if already bcrypt (starts with $2y$)
        if (str_starts_with($hash, '$2y$')) {
            return Hash::check($plain, $hash);
        }

        // Legacy MD5 verification
        if (hash_equals($hash, md5($plain))) {
            $user->password = Hash::make($plain);
            $user->save();
            return true;
        }

        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        $hash = $user->getAuthPassword();

        // If already bcrypt, skip unless forced
        if (!$force && str_starts_with($hash, '$2y$')) {
            return;
        }

        // Rehash only if credentials match legacy MD5
        if (hash_equals($hash, md5($credentials['password']))) {
            $user->password = Hash::make($credentials['password']);
            $user->save();
        }
    }
}

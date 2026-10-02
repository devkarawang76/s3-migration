<?php

namespace Tests\Unit\Providers;

use App\Providers\LegacyUserProvider;
use App\Models\LegacyUser;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LegacyUserProviderTest extends TestCase
{
    protected function fakeUser(): FakeLegacyUser
    {
        return new FakeLegacyUser();
    }

    public function test_provider_implements_user_provider_interface(): void
    {
        $provider = new LegacyUserProvider();
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\UserProvider::class, $provider);
    }

    public function test_validate_credentials_returns_false_for_invalid_md5_password(): void
    {
        $provider = new LegacyUserProvider();
        $user = $this->fakeUser();
        $user->password = md5('correct_password');

        $this->assertFalse($provider->validateCredentials($user, ['password' => 'wrong_password']));
        $this->assertSame(0, $user->saveCount);
        $this->assertSame(md5('correct_password'), $user->password);
    }

    public function test_validate_credentials_returns_true_for_valid_md5_password_and_rehashes(): void
    {
        $provider = new LegacyUserProvider();
        $user = $this->fakeUser();
        $user->password = md5('correct_password');

        $this->assertTrue($provider->validateCredentials($user, ['password' => 'correct_password']));
        $this->assertSame(1, $user->saveCount);
        $this->assertStringStartsWith('$2y$', $user->password);
        $this->assertTrue(Hash::check('correct_password', $user->password));
    }

    public function test_validate_credentials_accepts_bcrypt_password_without_rehash(): void
    {
        $provider = new LegacyUserProvider();
        $user = $this->fakeUser();
        $bcrypt = Hash::make('secret');
        $user->password = $bcrypt;

        $this->assertTrue($provider->validateCredentials($user, ['password' => 'secret']));
        $this->assertSame(0, $user->saveCount);
        $this->assertSame($bcrypt, $user->password);
    }

    public function test_validate_credentials_rejects_invalid_bcrypt_password(): void
    {
        $provider = new LegacyUserProvider();
        $user = $this->fakeUser();
        $user->password = Hash::make('secret');

        $this->assertFalse($provider->validateCredentials($user, ['password' => 'wrong']));
        $this->assertSame(0, $user->saveCount);
    }

    public function test_rehash_password_if_required_rehashes_md5_and_skips_bcrypt(): void
    {
        $provider = new LegacyUserProvider();

        $md5User = $this->fakeUser();
        $md5User->password = md5('legacy');
        $provider->rehashPasswordIfRequired($md5User, ['password' => 'legacy']);
        $this->assertSame(1, $md5User->saveCount);
        $this->assertStringStartsWith('$2y$', $md5User->password);

        $bcryptUser = $this->fakeUser();
        $hash = Hash::make('secret');
        $bcryptUser->password = $hash;
        $provider->rehashPasswordIfRequired($bcryptUser, ['password' => 'secret']);
        $this->assertSame(0, $bcryptUser->saveCount);
        $this->assertSame($hash, $bcryptUser->password);
    }

    public function test_rehash_password_if_required_does_not_write_wrong_password(): void
    {
        $provider = new LegacyUserProvider();
        $user = $this->fakeUser();
        $user->password = md5('correct');

        $provider->rehashPasswordIfRequired($user, ['password' => 'wrong']);

        $this->assertSame(0, $user->saveCount);
        $this->assertSame(md5('correct'), $user->password);
    }
}

class FakeLegacyUser extends LegacyUser
{
    public int $saveCount = 0;

    public function save(array $options = []): bool
    {
        $this->saveCount++;
        return true;
    }
}
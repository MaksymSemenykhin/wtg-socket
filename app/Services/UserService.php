<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Create a new user and fire Registered event.
     */
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        event(new Registered($user));

        return $user;
    }

    /**
     * Update user profile (name, email). Clears email_verified_at if email changed.
     */
    public function updateProfile(User $user, array $data): void
    {
        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }

    /**
     * Update user password.
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => $newPassword,
        ]);
    }

    /**
     * Set new password after reset (e.g. from forgot password flow).
     */
    public function setPasswordFromReset(User $user, string $newPassword): void
    {
        $user->forceFill([
            'password' => $newPassword,
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));
    }

    /**
     * Delete user account.
     */
    public function deleteUser(User $user): void
    {
        $user->delete();
    }
}

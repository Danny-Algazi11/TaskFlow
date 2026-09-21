<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\RegisterUserData;
use App\Domain\Auth\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * "Create a new account." The one business rule here is "passwords are
 * hashed before they're stored" — everything else (uniqueness, format) is
 * input validation and belongs in the FormRequest, not here.
 *
 * Note what's deliberately absent: this does NOT log the user in. Starting
 * a session is an HTTP/infrastructure concern (a cookie), not a fact about
 * the business — so that call lives in the Controller instead.
 */
class RegisterUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function execute(RegisterUserData $data): User
    {
        return $this->users->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);
    }
}

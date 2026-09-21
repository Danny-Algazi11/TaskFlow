<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\LoginUserData;
use App\Domain\Auth\Exceptions\InvalidCredentialsException;
use App\Domain\Auth\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * "Are these credentials valid?" — that's the whole business rule, and it's
 * the reason this class exists as a Use Case rather than living inline in
 * the controller: it's a decision worth naming and testing on its own.
 *
 * It intentionally does NOT call Auth::attempt() or Auth::login(). Verifying
 * a password is business logic; establishing a session cookie is a framework
 * detail the Controller handles after this Use Case says "yes, this is them".
 */
class LoginUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    /**
     * @throws InvalidCredentialsException
     */
    public function execute(LoginUserData $data): User
    {
        $user = $this->users->findByEmail($data->email);

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        return $user;
    }
}

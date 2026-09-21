<?php

namespace App\Http\Controllers\Api;

use App\Application\Auth\DTOs\LoginUserData;
use App\Application\Auth\DTOs\RegisterUserData;
use App\Application\Auth\UseCases\LoginUserUseCase;
use App\Application\Auth\UseCases\RegisterUserUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Deliberately thin: validate input (via FormRequest), hand it to a Use
 * Case as a DTO, translate the result into an HTTP response. No business
 * logic lives here — that's the whole point of the layers underneath.
 *
 * logout() and user() stay as one-liners with no Use Case behind them:
 * there's no business rule to name, just framework session mechanics /
 * a plain read. Wrapping those in a Use Case would be ceremony with
 * nothing underneath it.
 *
 * Every Auth call here names the 'web' guard explicitly rather than
 * relying on Laravel's "default guard". The auth:sanctum middleware (on
 * /logout and /user) calls Auth::shouldUse('sanctum') once a request is
 * authenticated, which silently swaps the default guard for the *rest of
 * that request*. Sanctum's guard is a stateless RequestGuard with no
 * login()/logout() methods, so an un-namespaced Auth::login() or
 * Auth::logout() called anywhere downstream of that swap would blow up.
 * Naming 'web' sidesteps the whole footgun.
 *
 * Neither action catches InvalidCredentialsException — it's a
 * DomainException, so bootstrap/app.php's exception handler turns it into
 * the right JSON response on its own. See that file for why.
 */
class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserUseCase $useCase)
    {
        $user = $useCase->execute(new RegisterUserData(
            name: $request->string('name')->value(),
            email: $request->string('email')->value(),
            password: $request->string('password')->value(),
        ));

        Auth::guard('web')->login($user);

        return response()->json(new UserResource($user));
    }

    public function login(LoginRequest $request, LoginUserUseCase $useCase)
    {
        $user = $useCase->execute(new LoginUserData(
            email: $request->string('email')->value(),
            password: $request->string('password')->value(),
        ));

        Auth::guard('web')->login($user);

        return response()->json(new UserResource($user));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();

        return response()->noContent();
    }

    public function user(Request $request)
    {
        return response()->json(new UserResource($request->user()));
    }
}

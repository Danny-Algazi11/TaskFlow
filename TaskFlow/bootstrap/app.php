<?php

use App\Domain\Shared\DomainException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    // Registers POST /api/broadcasting/auth under auth:sanctum, matching
    // every other endpoint in this app — the frontend's Echo client hits
    // this with its session cookie to prove who's asking to listen on a
    // channel, and routes/channels.php's authorizer decides yes or no.
    //
    // EnsureFrontendRequestsAreStateful has to be listed explicitly here
    // (not just auth:sanctum) because this route is registered outside
    // routes/api.php's own route group — $middleware->statefulApi() below
    // only prepends that middleware to the 'api' group, which this
    // broadcasting route isn't part of. Without it, auth:sanctum has
    // nothing telling it the session cookie should be trusted, so it falls
    // back to expecting a Bearer token and every request 401s.
    ->withBroadcasting(
        channels: __DIR__ . '/../routes/channels.php',
        attributes: [
            'prefix' => 'api',
            'middleware' => [\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, 'auth:sanctum'],
        ],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );

        // Every business-rule failure across every feature funnels through
        // here: any DomainException (InvalidCredentials, WorkspaceNotFound,
        // WorkspaceAccessDenied, ...) already knows its own HTTP status, so
        // one handler covers all of them — controllers never catch these.
        $exceptions->render(function (DomainException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], $e->httpStatus());
            }
        });
    })->create();

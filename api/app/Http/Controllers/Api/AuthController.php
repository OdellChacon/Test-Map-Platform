<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $this->ensureSessionIsAvailable($request);

        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request): UserResource
    {
        $this->ensureSessionIsAvailable($request);

        if (! Auth::attempt($request->validated())) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $request->session()->regenerate();

        return new UserResource(Auth::user());
    }

    public function logout(Request $request): JsonResponse
    {
        $this->ensureSessionIsAvailable($request);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(status: 204);
    }

    /**
     * Sanctum only binds a session to the request when the Origin/Referer header matches a
     * configured stateful domain; a client that omits both (e.g. a bare curl/API call) never
     * gets one, and calling $request->session() would otherwise throw an unhandled RuntimeException.
     */
    private function ensureSessionIsAvailable(Request $request): void
    {
        if (! $request->hasSession()) {
            throw new AuthenticationException(
                'This endpoint requires a request from a trusted frontend origin (missing or unrecognized Origin/Referer header).'
            );
        }
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}

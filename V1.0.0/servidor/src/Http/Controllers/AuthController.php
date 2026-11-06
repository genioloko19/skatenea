<?php

declare(strict_types=1);

namespace Skatenea\Http\Controllers;

use Skatenea\Application\PasswordReset\PasswordResetService;
use Skatenea\Application\PreferencesService;
use Skatenea\Auth\AuthService;
use Skatenea\Auth\CookieService;
use Skatenea\Auth\Rbac;
use Skatenea\Core\Request;
use Skatenea\Core\Response;

final class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly CookieService $cookies,
        private readonly Rbac $rbac,
        private readonly PreferencesService $preferences,
        private readonly PasswordResetService $passwordReset
    ) {
    }

    public function login(Request $request): Response
    {
        $data = $this->validated($request, [
            'email' => 'required|string|email|max:190',
            'password' => 'required|string|max:255',
        ]);

        $result = $this->auth->login($data['email'], $data['password'], $request);
        $this->cookies->setRefreshToken($result['refresh_token'], (int) $result['refresh_expires_in']);
        $this->cookies->setCsrfToken($result['csrf_token'], (int) $result['refresh_expires_in']);

        return Response::data([
            'access_token' => $result['access_token'],
            'token_type' => 'Bearer',
            'expires_in' => $result['expires_in'],
            'csrf_token' => $result['csrf_token'],
            'user' => $result['user'],
            'permissions' => $this->rbac->permissionsFor($result['user']['role']),
        ]);
    }

    public function refresh(Request $request): Response
    {
        $result = $this->auth->refresh($request);
        $this->cookies->setRefreshToken($result['refresh_token'], (int) $result['refresh_expires_in']);
        $this->cookies->setCsrfToken($result['csrf_token'], (int) $result['refresh_expires_in']);

        return Response::data([
            'access_token' => $result['access_token'],
            'token_type' => 'Bearer',
            'expires_in' => $result['expires_in'],
            'csrf_token' => $result['csrf_token'],
            'user' => $result['user'],
            'permissions' => $this->rbac->permissionsFor($result['user']['role']),
        ]);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout($request->cookie(CookieService::REFRESH_COOKIE), $request);
        $this->cookies->clear();

        return Response::noContent();
    }

    public function me(Request $request): Response
    {
        $user = $this->user($request);

        return Response::data([
            'user' => $user->toPublicArray(),
            'permissions' => $this->rbac->permissionsFor($user->role),
            'preferences' => $this->preferences->get($user),
        ]);
    }

    public function register(Request $request): Response
    {
        $data = $this->validated($request, [
            'full_name' => 'required|string|min:3|max:150',
            'email' => 'required|string|email|max:190',
            'password' => 'required|string|min:12|max:255',
            'phone' => 'required|string|min:7|max:30',
            'document_type' => 'nullable|string|in:CC,CE,PA,NIT',
            'document_number' => 'nullable|string|max:50',
        ]);

        $user = $this->auth->registerBuyer($data, $request);

        return Response::data(['user' => $user], 201);
    }

    public function forgotPassword(Request $request): Response
    {
        $data = $this->validated($request, [
            'email' => 'required|string|email|max:190',
        ]);
        $this->passwordReset->request((string) $data['email'], $request);

        return Response::data([
            'message' => 'Si la cuenta existe, enviaremos un enlace de recuperación al correo registrado.',
        ], 202);
    }

    public function resetPassword(Request $request): Response
    {
        $data = $this->validated($request, [
            'token' => 'required|string|min:16|max:255',
            'password' => 'required|string|min:12|max:255',
        ]);
        $this->passwordReset->reset((string) $data['token'], (string) $data['password'], $request);

        return Response::data(['message' => 'Contraseña actualizada. Inicia sesión nuevamente.']);
    }

    public function changePassword(Request $request): Response
    {
        $user = $this->user($request);
        $data = $this->validated($request, [
            'current_password' => 'required|string|max:255',
            'new_password' => 'required|string|min:12|max:255',
        ]);

        $this->auth->changePassword($user, $data['current_password'], $data['new_password'], $request);

        return Response::noContent();
    }
}

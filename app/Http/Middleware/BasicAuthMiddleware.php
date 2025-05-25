<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Request;

class BasicAuthMiddleware {
    public function handle(Request $request, Closure $next): mixed {
        $username = $request->getUser();
        $password = $request->getPassword();

        if (! $username || ! $password) {
            return $this->unauthorizedResponse();
        }

        if (! Auth::attempt(['username' => $username, 'password' => $password])) {
            return $this->unauthorizedResponse();
        }

        return $next($request);
    }

    protected function unauthorizedResponse(): JsonResponse {
        return response()->json(['error' => 'Unauthorized'], 401)
            ->header('WWW-Authenticate', 'Basic realm="My Realm"');
    }
}

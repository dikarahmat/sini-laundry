<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi akses route berdasarkan peran (App\Enums\Role).
 * Penggunaan: ->middleware('role:admin,owner')
 */
class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles  Daftar peran yang diizinkan, boleh dipisahkan koma.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = collect($roles)
            ->flatMap(fn (string $role): array => explode(',', $role))
            ->map(fn (string $role): string => trim($role))
            ->filter()
            ->all();

        if ($user === null || ! in_array($user->role?->value, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}

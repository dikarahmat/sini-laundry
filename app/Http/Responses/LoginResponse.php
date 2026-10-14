<?php

namespace App\Http\Responses;

use App\Enums\Role;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect setelah login disesuaikan dengan peran pengguna
 * (architecture.md bagian 7 dan tasks.md T1.4).
 */
class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        return redirect()->to($this->redirectPath($request));
    }

    /**
     * Tujuan redirect berdasarkan peran.
     */
    protected function redirectPath(Request $request): string
    {
        $user = $request->user();

        return match ($user?->role) {
            Role::Admin => route('admin.pesanan.index', absolute: false),
            Role::Owner => route('owner.dashboard', absolute: false),
            default => route('pesanan.index', absolute: false),
        };
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Hesabınız pasif durumda. Yönetici ile iletişime geçin.']);
        }

        return $next($request);
    }
}

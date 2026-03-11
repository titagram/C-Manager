<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  Il permesso richiesto (es. 'magazzino.carico')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Il tuo account è stato disattivato.');
        }

        if (!$user->hasPermission($permission)) {
            abort(403, 'Non hai i permessi per eseguire questa operazione.');
        }

        return $next($request);
    }
}

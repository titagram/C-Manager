<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Ruoli ammessi (es. 'admin', 'operatore')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
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

        // Admin ha sempre accesso
        if ($user->role === UserRole::ADMIN) {
            return $next($request);
        }

        // Verifica se il ruolo dell'utente è tra quelli ammessi
        foreach ($roles as $role) {
            if ($user->role->value === $role) {
                return $next($request);
            }
        }

        abort(403, 'Non hai i permessi per accedere a questa risorsa.');
    }
}

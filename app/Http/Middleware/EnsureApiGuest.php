<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Versão da middleware "guest" própria para a API.
 *
 * O `\Illuminate\Auth\Middleware\RedirectIfAuthenticated` padrão do Laravel
 * SEMPRE faz redirect() quando o usuário já está autenticado — ele nunca
 * checa se a request espera JSON. Numa API, isso faz `POST /login` ou
 * `POST /register` devolverem um 302 pra "/", que (fora do Laravel) cai
 * na página estática do servidor em vez de um erro tratável pelo client.
 *
 * Aqui, se já autenticado, devolve 409 com JSON em vez de redirecionar.
 */
class EnsureApiGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard()->check()) {
            return response()->json([
                'message' => 'Você já está autenticado.',
            ], 409);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Força toda requisição da API a ser tratada como "espera JSON".
 *
 * Sem isso, um client que não manda o header `Accept: application/json`
 * (ex.: Postman com Accept padrão `* / *`) faz o Laravel tratar erros de
 * validação e autenticação como se fosse uma navegação de browser normal:
 * em vez de devolver 422/401 com JSON, ele tenta redirecionar
 * (redirect()->back() ou pra rota "login"), e sem header Referer esse
 * redirect cai em "/" — que aqui é servido pelo Nginx/aaPanel, não pelo
 * Laravel. O resultado prático era a API devolvendo a página HTML padrão
 * do painel em vez do erro de validação esperado.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}

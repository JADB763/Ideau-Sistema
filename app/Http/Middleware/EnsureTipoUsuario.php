<?php

namespace App\Http\Middleware;

use App\Enums\TipoUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe a rota a tipos de usuário. Uso: ->middleware('tipo:Coordenacao,Professor')
 * (nomes dos cases de TipoUsuario).
 */
class EnsureTipoUsuario
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$tipos): Response
    {
        $permitidos = array_map(fn (string $nome) => constant(TipoUsuario::class.'::'.$nome), $tipos);

        abort_unless(in_array($request->user()?->tipo_usuario, $permitidos, true), 403);

        return $next($request);
    }
}

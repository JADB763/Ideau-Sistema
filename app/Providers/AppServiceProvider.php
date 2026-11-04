<?php

namespace App\Providers;

use App\Models\DocumentoEstagio;
use App\Models\Empresa;
use App\Models\Estagio;
use App\Models\Solicitacao;
use App\Models\Tcc;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nomes curtos gravados em Evento.entidade_type, em vez do nome da classe.
        Relation::enforceMorphMap([
            'usuario' => Usuario::class,
            'empresa' => Empresa::class,
            'estagio' => Estagio::class,
            'tcc' => Tcc::class,
            'documento' => DocumentoEstagio::class,
            'solicitacao' => Solicitacao::class,
        ]);
    }
}

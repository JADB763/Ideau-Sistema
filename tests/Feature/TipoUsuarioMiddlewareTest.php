<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'tipo:Coordenacao'])->get('/_teste/coordenacao', fn () => 'ok');
    Route::middleware(['web', 'auth', 'tipo:Aluno,Professor'])->get('/_teste/academico', fn () => 'ok');
});

it('libera a rota só para os tipos indicados', function (string $estado, string $rota, int $status) {
    $usuario = Usuario::factory()->{$estado}()->create();

    $this->actingAs($usuario)->get($rota)->assertStatus($status);
})->with([
    'coordenação na rota da coordenação' => ['coordenacao', '/_teste/coordenacao', 200],
    'professor na rota da coordenação' => ['professor', '/_teste/coordenacao', 403],
    'aluno na rota da coordenação' => ['aluno', '/_teste/coordenacao', 403],
    'aluno na rota acadêmica' => ['aluno', '/_teste/academico', 200],
    'professor na rota acadêmica' => ['professor', '/_teste/academico', 200],
    'coordenação na rota acadêmica' => ['coordenacao', '/_teste/academico', 403],
]);

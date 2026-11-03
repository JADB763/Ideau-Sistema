<?php

namespace Database\Factories;

use App\Enums\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $senha;

    /**
     * Define the model's default state (aluno).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome_completo' => fake()->name(),
            'data_nascimento' => fake()->date('Y-m-d', '-18 years'),
            'cpf' => fake()->unique()->cpf(false),
            'email' => fake()->unique()->safeEmail(),
            'senha' => static::$senha ??= Hash::make('password'),
            'tipo_usuario' => TipoUsuario::Aluno,
            'matricula' => fake()->unique()->numerify('######'),
            'curso' => fake()->randomElement(['Sistemas de Informação', 'Engenharia de Software', 'Administração']),
            'instituicao' => 'IDEAU',
            'ativo' => true,
        ];
    }

    public function professor(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_usuario' => TipoUsuario::Professor,
            'matricula' => null,
            'curso' => null,
            'area' => fake()->randomElement(['Computação', 'Gestão', 'Matemática']),
        ]);
    }

    public function coordenacao(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_usuario' => TipoUsuario::Coordenacao,
            'matricula' => null,
            'curso' => null,
        ]);
    }

    public function inativo(): static
    {
        return $this->state(fn (array $attributes) => [
            'ativo' => false,
        ]);
    }
}

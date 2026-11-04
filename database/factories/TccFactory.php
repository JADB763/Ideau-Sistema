<?php

namespace Database\Factories;

use App\Enums\TccStatus;
use App\Models\Tcc;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tcc>
 */
class TccFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_aluno' => Usuario::factory(),
            'id_professor_orientador' => Usuario::factory()->professor(),
            'titulo' => fake()->sentence(6),
            'resumo' => fake()->paragraph(),
            'status' => TccStatus::Proposta,
        ];
    }
}

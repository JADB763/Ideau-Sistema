<?php

namespace Database\Factories;

use App\Enums\EstagioStatus;
use App\Models\Empresa;
use App\Models\Estagio;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estagio>
 */
class EstagioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-3 months', '+1 month');

        return [
            'id_empresa' => Empresa::factory(),
            'id_aluno' => Usuario::factory(),
            'id_professor_orientador' => Usuario::factory()->professor(),
            'data_inicio' => $inicio,
            'data_termino' => (clone $inicio)->modify('+6 months'),
            'status' => EstagioStatus::DocumentosIncompletos,
            'supervisor' => fake()->name(),
            'supervisor_email' => fake()->safeEmail(),
            'horas_semanais' => fake()->randomElement([20, 30, 40]),
            'atividades' => fake()->paragraph(),
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario::factory(10)->create();

        Usuario::factory()->coordenacao()->create([
            'nome_completo' => 'Coordenação Teste',
            'email' => 'coordenacao@example.com',
        ]);
    }
}

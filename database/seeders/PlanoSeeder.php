<?php

namespace Database\Seeders;

use App\Models\Plano;
use Illuminate\Database\Seeder;

class PlanoSeeder extends Seeder
{
    public function run(): void
    {
        $planos = [
            [
                'nome' => 'Grátis',
                'slug' => 'gratis',
                'descricao' => 'Para veterinários autônomos iniciando.',
                'preco_mensal' => 0,
                'preco_anual' => 0,
                'limite_usuarios' => 1,
                'limite_clinicas' => 1,
                'limite_consultas_mes' => 30,
                'limite_storage_gb' => 1,
                'consultas_ilimitadas' => false,
                'clinicas_ilimitadas' => false,
                'chat_audio' => false,
                'chat_audio_duracao_segundos' => 0,
                'chat_grupos' => false,
                'chat_grupos_max_membros' => 0,
                'ativo' => true,
                'ordem' => 1,
            ],
            [
                'nome' => 'Básico',
                'slug' => 'basico',
                'descricao' => 'Para clínicas pequenas em crescimento.',
                'preco_mensal' => 97.00,
                'preco_anual' => 970.00,
                'limite_usuarios' => 3,
                'limite_clinicas' => 3,
                'limite_consultas_mes' => 150,
                'limite_storage_gb' => 5,
                'consultas_ilimitadas' => false,
                'clinicas_ilimitadas' => false,
                'chat_audio' => true,
                'chat_audio_duracao_segundos' => 60,
                'chat_grupos' => true,
                'chat_grupos_max_membros' => 10,
                'ativo' => true,
                'ordem' => 2,
            ],
            [
                'nome' => 'Profissional',
                'slug' => 'profissional',
                'descricao' => 'Para clínicas estabelecidas com equipe.',
                'preco_mensal' => 197.00,
                'preco_anual' => 1970.00,
                'limite_usuarios' => 10,
                'limite_clinicas' => 5,
                'limite_consultas_mes' => 0,
                'limite_storage_gb' => 20,
                'consultas_ilimitadas' => true,
                'clinicas_ilimitadas' => false,
                'chat_audio' => true,
                'chat_audio_duracao_segundos' => 180,
                'chat_grupos' => true,
                'chat_grupos_max_membros' => 0,
                'ativo' => true,
                'ordem' => 3,
            ],
            [
                'nome' => 'Empresarial',
                'slug' => 'empresarial',
                'descricao' => 'Para redes de clínicas com múltiplas unidades.',
                'preco_mensal' => 397.00,
                'preco_anual' => 3970.00,
                'limite_usuarios' => 50,
                'limite_clinicas' => 0,
                'limite_consultas_mes' => 0,
                'limite_storage_gb' => 100,
                'consultas_ilimitadas' => true,
                'clinicas_ilimitadas' => true,
                'chat_audio' => true,
                'chat_audio_duracao_segundos' => 300,
                'chat_grupos' => true,
                'chat_grupos_max_membros' => 0,
                'ativo' => true,
                'ordem' => 4,
            ],
        ];

        foreach ($planos as $plano) {
            Plano::firstOrCreate(['slug' => $plano['slug']], $plano);
        }
    }
}

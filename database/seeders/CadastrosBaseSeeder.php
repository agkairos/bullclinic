<?php

namespace Database\Seeders;

use App\Models\CategoriaFinanceira;
use App\Models\CategoriaProduto;
use App\Models\Clinica;
use App\Models\TipoServico;
use Illuminate\Database\Seeder;

class CadastrosBaseSeeder extends Seeder
{
    public function run(): void
    {
        $clinicas = Clinica::all();
        if ($clinicas->isEmpty()) {
            $this->command->warn('Nenhuma clínica encontrada. Pulando CadastrosBaseSeeder.');
            return;
        }


        // Tipos de serviço padrão (clinica_id = null = disponíveis para todas)
        $tiposServico = [
            ['nome' => 'Consulta', 'descricao' => 'Consulta clínica geral', 'duracao_minutos' => 30, 'preco' => 120.00, 'cor' => '#00c1b3'],
            ['nome' => 'Retorno', 'descricao' => 'Retorno de consulta anterior', 'duracao_minutos' => 20, 'preco' => 60.00, 'cor' => '#6366f1'],
            ['nome' => 'Emergência', 'descricao' => 'Atendimento de urgência', 'duracao_minutos' => 60, 'preco' => 250.00, 'cor' => '#ef4444'],
            ['nome' => 'Vacinação', 'descricao' => 'Aplicação de vacina', 'duracao_minutos' => 15, 'preco' => 80.00, 'cor' => '#f59e0b'],
            ['nome' => 'Cirurgia', 'descricao' => 'Procedimento cirúrgico', 'duracao_minutos' => 120, 'preco' => 500.00, 'cor' => '#8b5cf6'],
            ['nome' => 'Exame', 'descricao' => 'Coleta para exame laboratorial', 'duracao_minutos' => 20, 'preco' => 90.00, 'cor' => '#ec4899'],
            ['nome' => 'Banho e Tosa', 'descricao' => 'Serviço de estética pet', 'duracao_minutos' => 90, 'preco' => 70.00, 'cor' => '#14b8a6'],
        ];

        // Categorias financeiras padrão
        $categoriasFinanceiras = [
            ['nome' => 'Consulta', 'tipo' => 'receita'],
            ['nome' => 'Vacinação', 'tipo' => 'receita'],
            ['nome' => 'Exame', 'tipo' => 'receita'],
            ['nome' => 'Cirurgia', 'tipo' => 'receita'],
            ['nome' => 'Medicamento', 'tipo' => 'receita'],
            ['nome' => 'Estética', 'tipo' => 'receita'],
            ['nome' => 'Outros (receita)', 'tipo' => 'receita'],
            ['nome' => 'Aluguel', 'tipo' => 'despesa'],
            ['nome' => 'Salários', 'tipo' => 'despesa'],
            ['nome' => 'Medicamentos (compra)', 'tipo' => 'despesa'],
            ['nome' => 'Insumos / Materiais', 'tipo' => 'despesa'],
            ['nome' => 'Equipamentos', 'tipo' => 'despesa'],
            ['nome' => 'Marketing', 'tipo' => 'despesa'],
            ['nome' => 'Água / Luz / Internet', 'tipo' => 'despesa'],
            ['nome' => 'Outros (despesa)', 'tipo' => 'despesa'],
        ];

        // Categorias de produto padrão
        $categoriasProduto = [
            ['nome' => 'Medicamentos', 'descricao' => 'Antibióticos, anti-inflamatórios, etc.'],
            ['nome' => 'Vacinas', 'descricao' => 'Vacinas e imunobiológicos'],
            ['nome' => 'Antiparasitários', 'descricao' => 'Antipulgas, carrapaticidas, vermífugos'],
            ['nome' => 'Insumos Cirúrgicos', 'descricao' => 'Fios, luvas, curativos'],
            ['nome' => 'Descartáveis', 'descricao' => 'Seringas, agulhas, máscaras'],
            ['nome' => 'Rações', 'descricao' => 'Ração seca, úmida e terapêutica'],
            ['nome' => 'Acessórios', 'descricao' => 'Coleiras, caixas de transporte, etc.'],
            ['nome' => 'Higiene', 'descricao' => 'Shampoo, condicionador, produtos de tosa'],
        ];

        foreach ($clinicas as $clinica) {
            foreach ($tiposServico as $ts) {
                TipoServico::withoutGlobalScopes()->firstOrCreate(
                    ['nome' => $ts['nome'], 'clinica_id' => $clinica->id],
                    array_merge($ts, ['clinica_id' => $clinica->id, 'ativo' => true])
                );
            }

            foreach ($categoriasFinanceiras as $cf) {
                CategoriaFinanceira::withoutGlobalScopes()->firstOrCreate(
                    ['nome' => $cf['nome'], 'clinica_id' => $clinica->id],
                    array_merge($cf, ['clinica_id' => $clinica->id, 'ativo' => true])
                );
            }

            foreach ($categoriasProduto as $cp) {
                CategoriaProduto::withoutGlobalScopes()->firstOrCreate(
                    ['nome' => $cp['nome'], 'clinica_id' => $clinica->id],
                    array_merge($cp, ['clinica_id' => $clinica->id, 'ativo' => true])
                );
            }
        }
    }
}

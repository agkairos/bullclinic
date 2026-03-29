<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Agenda
            ['nome' => 'ver_agenda', 'modulo' => 'agenda'],
            ['nome' => 'criar_agendamentos', 'modulo' => 'agenda'],
            ['nome' => 'editar_agendamentos', 'modulo' => 'agenda'],
            ['nome' => 'excluir_agendamentos', 'modulo' => 'agenda'],
            ['nome' => 'fazer_checkin', 'modulo' => 'agenda'],
            ['nome' => 'iniciar_atendimento', 'modulo' => 'agenda'],
            ['nome' => 'finalizar_atendimento', 'modulo' => 'agenda'],
            ['nome' => 'configuracoes_agenda', 'modulo' => 'agenda'],

            // Clientes
            ['nome' => 'ver_clientes', 'modulo' => 'clientes'],
            ['nome' => 'criar_clientes', 'modulo' => 'clientes'],
            ['nome' => 'editar_clientes', 'modulo' => 'clientes'],
            ['nome' => 'excluir_clientes', 'modulo' => 'clientes'],

            // Pacientes
            ['nome' => 'ver_pacientes', 'modulo' => 'pacientes'],
            ['nome' => 'criar_pacientes', 'modulo' => 'pacientes'],
            ['nome' => 'editar_pacientes', 'modulo' => 'pacientes'],
            ['nome' => 'excluir_pacientes', 'modulo' => 'pacientes'],

            // Prontuário
            ['nome' => 'ver_prontuario', 'modulo' => 'prontuario'],
            ['nome' => 'criar_consultas', 'modulo' => 'prontuario'],
            ['nome' => 'editar_consultas', 'modulo' => 'prontuario'],
            ['nome' => 'excluir_consultas', 'modulo' => 'prontuario'],
            ['nome' => 'prescrever_receitas', 'modulo' => 'prontuario'],
            ['nome' => 'solicitar_exames', 'modulo' => 'prontuario'],
            ['nome' => 'registrar_resultados_exames', 'modulo' => 'prontuario'],
            ['nome' => 'registrar_procedimentos', 'modulo' => 'prontuario'],
            ['nome' => 'registrar_vacinas', 'modulo' => 'prontuario'],

            // Estoque
            ['nome' => 'ver_estoque', 'modulo' => 'estoque'],
            ['nome' => 'adicionar_produtos', 'modulo' => 'estoque'],
            ['nome' => 'editar_produtos', 'modulo' => 'estoque'],
            ['nome' => 'excluir_produtos', 'modulo' => 'estoque'],
            ['nome' => 'movimentar_estoque', 'modulo' => 'estoque'],

            // Financeiro
            ['nome' => 'ver_financeiro', 'modulo' => 'financeiro'],
            ['nome' => 'criar_receitas', 'modulo' => 'financeiro'],
            ['nome' => 'criar_despesas', 'modulo' => 'financeiro'],
            ['nome' => 'editar_lancamentos', 'modulo' => 'financeiro'],
            ['nome' => 'excluir_lancamentos', 'modulo' => 'financeiro'],
            ['nome' => 'gerar_relatorios', 'modulo' => 'financeiro'],

            // Admin
            ['nome' => 'gerenciar_usuarios', 'modulo' => 'admin'],
            ['nome' => 'gerenciar_roles', 'modulo' => 'admin'],
            ['nome' => 'gerenciar_permissoes', 'modulo' => 'admin'],
            ['nome' => 'configurar_clinica', 'modulo' => 'admin'],
            ['nome' => 'ver_auditoria', 'modulo' => 'admin'],
            ['nome' => 'gerenciar_unidades', 'modulo' => 'admin'],
            ['nome' => 'alternar_entre_unidades', 'modulo' => 'admin'],
            ['nome' => 'ver_relatorio_consolidado', 'modulo' => 'admin'],

            // Chat
            ['nome' => 'acessar_chat', 'modulo' => 'chat'],
            ['nome' => 'criar_conversas', 'modulo' => 'chat'],
            ['nome' => 'enviar_mensagens', 'modulo' => 'chat'],
            ['nome' => 'excluir_mensagens', 'modulo' => 'chat'],
            ['nome' => 'gerenciar_conversas_grupo', 'modulo' => 'chat'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['nome' => $permission['nome']], $permission);
        }
    }
}

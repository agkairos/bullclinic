<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Roles de sistema (clinica_id = null → disponíveis para todas as clínicas)
        $roles = [
            [
                'nome' => 'super_admin',
                'display_name' => 'Super Admin',
                'descricao' => 'Acesso total ao sistema.',
                'is_system' => true,
                'permissions' => Permission::pluck('nome')->toArray(), // todas
            ],
            [
                'nome' => 'admin',
                'display_name' => 'Administrador',
                'descricao' => 'Administrador da clínica com acesso completo.',
                'is_system' => true,
                'permissions' => Permission::pluck('nome')->toArray(), // todas
            ],
            [
                'nome' => 'veterinario',
                'display_name' => 'Veterinário',
                'descricao' => 'Acesso ao prontuário e agenda.',
                'is_system' => true,
                'permissions' => [
                    'ver_agenda', 'criar_agendamentos', 'editar_agendamentos',
                    'fazer_checkin', 'iniciar_atendimento', 'finalizar_atendimento',
                    'ver_clientes', 'criar_clientes', 'editar_clientes',
                    'ver_pacientes', 'criar_pacientes', 'editar_pacientes',
                    'ver_prontuario', 'criar_consultas', 'editar_consultas',
                    'prescrever_receitas', 'solicitar_exames', 'registrar_resultados_exames',
                    'registrar_procedimentos', 'registrar_vacinas',
                    'ver_estoque',
                    'acessar_chat', 'criar_conversas', 'enviar_mensagens', 'excluir_mensagens',
                ],
            ],
            [
                'nome' => 'recepcionista',
                'display_name' => 'Recepcionista',
                'descricao' => 'Acesso à agenda e cadastro de clientes.',
                'is_system' => true,
                'permissions' => [
                    'ver_agenda', 'criar_agendamentos', 'editar_agendamentos',
                    'fazer_checkin',
                    'ver_clientes', 'criar_clientes', 'editar_clientes',
                    'ver_pacientes', 'criar_pacientes', 'editar_pacientes',
                    'acessar_chat', 'criar_conversas', 'enviar_mensagens', 'excluir_mensagens',
                ],
            ],
        ];

        foreach ($roles as $data) {
            $permissoes = $data['permissions'];
            unset($data['permissions']);

            $role = Role::firstOrCreate(
                ['clinica_id' => null, 'nome' => $data['nome']],
                $data
            );

            $permissionIds = Permission::whereIn('nome', $permissoes)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}

<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\HorarioAtendimento;
use Carbon\Carbon;

class DisponibilidadeService
{
    /**
     * Retorna os slots livres de um veterinário em uma data.
     *
     * @return array<array{hora_inicio: string, hora_fim: string}>
     */
    public function slotsLivres(int $veterinarioId, string $data): array
    {
        $carbon   = Carbon::parse($data);
        $diaSemana = (int) $carbon->format('N') % 7; // 0=dom,1=seg,...,6=sab (igual ao DB)

        $horario = HorarioAtendimento::where('user_id', $veterinarioId)
            ->where('dia_semana', $diaSemana)
            ->where('ativo', true)
            ->first();

        if (! $horario) {
            return [];
        }

        $duracao   = $horario->duracao_consulta_minutos;
        $intervalo = $horario->intervalo_entre_consultas_minutos;
        $passo     = $duracao + $intervalo;

        $inicio = Carbon::parse("{$data} {$horario->hora_inicio}");
        $fim    = Carbon::parse("{$data} {$horario->hora_fim}");

        // Agendamentos já existentes neste dia/vet (não cancelados)
        $ocupados = Agendamento::where('veterinario_id', $veterinarioId)
            ->where('data', $data)
            ->whereNotIn('status', ['cancelado'])
            ->get(['hora_inicio', 'hora_fim'])
            ->map(fn($a) => [
                'inicio' => Carbon::parse("{$data} {$a->hora_inicio}"),
                'fim'    => Carbon::parse("{$data} {$a->hora_fim}"),
            ]);

        $slots = [];
        $cursor = $inicio->copy();

        while ($cursor->copy()->addMinutes($duracao)->lte($fim)) {
            $slotFim = $cursor->copy()->addMinutes($duracao);

            $livre = $ocupados->every(function ($oc) use ($cursor, $slotFim) {
                // Sem sobreposição: slot termina antes do ocupado começar OU começa depois do ocupado terminar
                return $slotFim->lte($oc['inicio']) || $cursor->gte($oc['fim']);
            });

            if ($livre) {
                $slots[] = [
                    'hora_inicio' => $cursor->format('H:i'),
                    'hora_fim'    => $slotFim->format('H:i'),
                ];
            }

            $cursor->addMinutes($passo);
        }

        return $slots;
    }
}

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; padding: 20px; }
    .header { border-bottom: 2px solid #00c1b3; padding-bottom: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: flex-start; }
    .clinic-name { font-size: 16px; font-weight: bold; color: #00c1b3; }
    .doc-title { font-size: 13px; font-weight: bold; color: #374151; margin-top: 2px; }
    .date { font-size: 10px; color: #6b7280; }
    .section { margin-bottom: 14px; }
    .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin-bottom: 8px; }
    .grid-2 { display: table; width: 100%; }
    .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 16px; }
    .field { margin-bottom: 6px; }
    .label { font-size: 9px; color: #9ca3af; text-transform: uppercase; }
    .value { font-size: 11px; color: #1f2937; font-weight: 500; }
    .consulta-block { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px; margin-bottom: 10px; }
    .consulta-header { display: flex; justify-content: space-between; margin-bottom: 6px; }
    .consulta-tipo { font-size: 11px; font-weight: bold; color: #374151; }
    .consulta-data { font-size: 10px; color: #6b7280; }
    .consulta-vet { font-size: 10px; color: #6b7280; }
    .text-block { background: #f9fafb; border-radius: 3px; padding: 6px 8px; margin-bottom: 4px; }
    .text-label { font-size: 9px; font-weight: bold; color: #6b7280; margin-bottom: 2px; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .badge-green { background: #dcfce7; color: #15803d; }
    .badge-yellow { background: #fef9c3; color: #a16207; }
    .vacina-row { border-bottom: 1px solid #f3f4f6; padding: 5px 0; display: flex; justify-content: space-between; }
    .footer { border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 20px; font-size: 9px; color: #9ca3af; text-align: center; }
    .alerta { background: #fef2f2; border-left: 3px solid #ef4444; padding: 4px 8px; margin-top: 4px; font-size: 10px; color: #dc2626; }
</style>
</head>
<body>

<div class="header">
    <div>
        <div class="clinic-name">BullClinic</div>
        <div class="doc-title">Prontuário Completo</div>
    </div>
    <div style="text-align:right">
        <div class="date">Emitido em {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

{{-- Dados do paciente --}}
<div class="section">
    <div class="section-title">Paciente</div>
    <div class="grid-2">
        <div class="col">
            <div class="field"><div class="label">Nome</div><div class="value">{{ $paciente->nome }}</div></div>
            <div class="field"><div class="label">Espécie / Raça</div><div class="value">{{ ucfirst($paciente->especie) }}{{ $paciente->raca ? ' · ' . $paciente->raca : '' }}</div></div>
            <div class="field"><div class="label">Sexo</div><div class="value">{{ $paciente->sexo === 'macho' ? 'Macho' : ($paciente->sexo === 'femea' ? 'Fêmea' : 'N/I') }}</div></div>
        </div>
        <div class="col">
            <div class="field"><div class="label">Tutor</div><div class="value">{{ $paciente->cliente->nome }}</div></div>
            @if($paciente->data_nascimento)
            <div class="field"><div class="label">Nascimento</div><div class="value">{{ $paciente->data_nascimento->format('d/m/Y') }}</div></div>
            @endif
            @if($paciente->peso)
            <div class="field"><div class="label">Peso</div><div class="value">{{ $paciente->peso }} kg</div></div>
            @endif
            @if($paciente->microchip)
            <div class="field"><div class="label">Microchip</div><div class="value">{{ $paciente->microchip }}</div></div>
            @endif
        </div>
    </div>
    @if($paciente->alergias)
    <div class="alerta">⚠ Alergias: {{ $paciente->alergias }}</div>
    @endif
</div>

{{-- Histórico de vacinas --}}
@if($paciente->vacinacoes->count())
<div class="section">
    <div class="section-title">Histórico de Vacinações</div>
    @foreach($paciente->vacinacoes as $v)
    <div class="vacina-row">
        <span>{{ $v->vacina_nome }}{{ $v->lote ? ' — Lote: ' . $v->lote : '' }}</span>
        <span style="color:#6b7280">{{ $v->data_aplicacao->format('d/m/Y') }}{{ $v->proxima_dose_em ? ' · Próxima: ' . $v->proxima_dose_em->format('d/m/Y') : '' }}</span>
    </div>
    @endforeach
</div>
@endif

{{-- Consultas --}}
@if($paciente->consultas->count())
<div class="section">
    <div class="section-title">Consultas ({{ $paciente->consultas->count() }})</div>
    @foreach($paciente->consultas as $c)
    <div class="consulta-block">
        <div class="consulta-header">
            <span class="consulta-tipo">{{ ['primeira_consulta'=>'Primeira consulta','retorno'=>'Retorno','emergencia'=>'Emergência'][$c->tipo_consulta] ?? $c->tipo_consulta }}</span>
            <span>
                <span class="consulta-data">{{ $c->iniciada_em ? $c->iniciada_em->format('d/m/Y') : '—' }}</span>
                <span class="consulta-vet"> · {{ $c->veterinario->name }}</span>
                <span class="badge {{ $c->status === 'finalizada' ? 'badge-green' : 'badge-yellow' }}" style="margin-left:6px">{{ $c->status === 'finalizada' ? 'Finalizada' : 'Em andamento' }}</span>
            </span>
        </div>
        @if($c->anamnese)
        <div class="text-block"><div class="text-label">Anamnese</div>{!! $c->anamnese !!}</div>
        @endif
        @if($c->diagnostico)
        <div class="text-block"><div class="text-label">Diagnóstico</div>{!! $c->diagnostico !!}</div>
        @endif
        @if($c->prognostico)
        <div class="text-block"><div class="text-label">Prognóstico</div>{!! $c->prognostico !!}</div>
        @endif
        @if($c->procedimentos->count())
        <div style="margin-top:6px;font-size:10px;color:#374151"><strong>Procedimentos:</strong>
            @foreach($c->procedimentos as $p) {{ $p->tipo_procedimento }}{{ !$loop->last ? ', ' : '' }} @endforeach
        </div>
        @endif
        @if($c->vacinacoes->count())
        <div style="font-size:10px;color:#374151"><strong>Vacinas:</strong>
            @foreach($c->vacinacoes as $v) {{ $v->vacina_nome }}{{ !$loop->last ? ', ' : '' }} @endforeach
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif

<div class="footer">
    Documento gerado pelo sistema BullClinic · {{ now()->format('d/m/Y \à\s H:i') }}
</div>

</body>
</html>

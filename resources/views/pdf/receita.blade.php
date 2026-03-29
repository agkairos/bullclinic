<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.6; }
    .header { border-bottom: 2px solid #00c1b3; padding-bottom: 12px; margin-bottom: 16px; }
    .clinic-name { font-size: 18px; font-weight: bold; color: #00c1b3; }
    .doc-title { font-size: 13px; font-weight: bold; color: #374151; }
    .info-grid { display: table; width: 100%; margin-bottom: 16px; border: 1px solid #e5e7eb; border-radius: 4px; }
    .info-col { display: table-cell; padding: 10px 12px; width: 33.33%; border-right: 1px solid #f3f4f6; vertical-align: top; }
    .info-col:last-child { border-right: none; }
    .info-label { font-size: 9px; text-transform: uppercase; color: #9ca3af; margin-bottom: 2px; }
    .info-value { font-size: 11px; font-weight: 600; color: #1f2937; }
    .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #6b7280; margin-bottom: 10px; letter-spacing: 0.05em; }
    .med-item { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 12px; margin-bottom: 8px; }
    .med-name { font-size: 12px; font-weight: bold; color: #111827; margin-bottom: 4px; }
    .med-detail { font-size: 10px; color: #6b7280; margin-bottom: 2px; }
    .instrucoes { background: #f0fdfa; border-left: 3px solid #00c1b3; padding: 8px 12px; margin-top: 16px; font-size: 10px; color: #374151; }
    .signature { margin-top: 40px; border-top: 1px solid #e5e7eb; padding-top: 8px; text-align: center; font-size: 10px; color: #6b7280; }
    .footer { border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 20px; font-size: 9px; color: #9ca3af; text-align: center; }
    .rx { font-size: 28px; font-weight: bold; color: #00c1b3; float: right; margin-top: -8px; }
</style>
</head>
<body>

<div class="header">
    <div class="rx">℞</div>
    <div class="clinic-name">BullClinic</div>
    <div class="doc-title">Receita Veterinária</div>
</div>

<div class="info-grid">
    <div class="info-col">
        <div class="info-label">Paciente</div>
        <div class="info-value">{{ $receita->paciente->nome }}</div>
        <div style="font-size:10px;color:#6b7280">{{ ucfirst($receita->paciente->especie) }}{{ $receita->paciente->raca ? ' · ' . $receita->paciente->raca : '' }}</div>
    </div>
    <div class="info-col">
        <div class="info-label">Tutor</div>
        <div class="info-value">{{ $receita->paciente->cliente->nome }}</div>
    </div>
    <div class="info-col">
        <div class="info-label">Data de emissão</div>
        <div class="info-value">{{ $receita->emitida_em->format('d/m/Y') }}</div>
        <div style="font-size:10px;color:#6b7280">Veterinário: {{ $receita->veterinario->name }}</div>
    </div>
</div>

<div class="section-title">Medicamentos prescritos</div>

@forelse($receita->itens as $item)
<div class="med-item">
    <div class="med-name">{{ $item->medicamento_nome }}</div>
    @if($item->dosagem)
    <div class="med-detail">Dosagem: {{ $item->dosagem }}</div>
    @endif
    @if($item->frequencia)
    <div class="med-detail">Frequência: {{ $item->frequencia }}</div>
    @endif
    @if($item->duracao_tratamento)
    <div class="med-detail">Duração: {{ $item->duracao_tratamento }}</div>
    @endif
    @if($item->via_administracao)
    <div class="med-detail">Via: {{ $item->via_administracao }}</div>
    @endif
    @if($item->instrucoes_especificas)
    <div class="med-detail" style="margin-top:4px;font-style:italic">{{ $item->instrucoes_especificas }}</div>
    @endif
</div>
@empty
<p style="color:#9ca3af;font-size:11px">Nenhum medicamento prescrito.</p>
@endforelse

@if($receita->instrucoes_gerais)
<div class="instrucoes">
    <strong>Instruções gerais:</strong> {{ $receita->instrucoes_gerais }}
</div>
@endif

<div class="signature">
    ___________________________________<br>
    {{ $receita->veterinario->name }}<br>
    @if($receita->veterinario->crmv) CRMV: {{ $receita->veterinario->crmv }} @endif
</div>

<div class="footer">
    Receita nº {{ $receita->id }} · Gerada pelo BullClinic em {{ now()->format('d/m/Y \à\s H:i') }}
</div>

</body>
</html>

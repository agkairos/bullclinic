<?php

namespace App\Http\Controllers;

use App\Models\Conta;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WooviWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $appId = config('services.woovi.app_id');
        $signature = $request->header('x-webhook-signature');

        if ($signature && $signature !== $appId) {
            return response('Assinatura inválida.', 400);
        }

        $payload = $request->json()->all();
        $event = $payload['event'] ?? null;
        $charge = $payload['charge'] ?? [];

        if ($event !== 'OPENPIX:CHARGE_COMPLETED') {
            return response('OK', 200);
        }

        $additionalInfo = collect($charge['additionalInfo'] ?? []);
        $contaId       = $additionalInfo->firstWhere('key', 'conta_id')['value'] ?? null;
        $planoId       = $additionalInfo->firstWhere('key', 'plano_id')['value'] ?? null;
        $periodicidade = $additionalInfo->firstWhere('key', 'periodicidade')['value'] ?? 'mensal';

        if (! $contaId) return response('OK', 200);

        $conta = Conta::find($contaId);
        if (! $conta) return response('OK', 200);

        $vencimento = $periodicidade === 'anual' ? now()->addYear() : now()->addMonth();

        $updates = [
            'status_pagamento' => 'pago',
            'data_vencimento'  => $vencimento,
        ];

        if ($planoId) {
            $updates['plano_id'] = $planoId;
            $conta->clinicas()->update(['plano_id' => $planoId]);
        }

        if (! empty($charge['customer']['correlationID'])) {
            $updates['woovi_customer_id'] = $charge['customer']['correlationID'];
        }

        $conta->update($updates);

        return response('OK', 200);
    }
}

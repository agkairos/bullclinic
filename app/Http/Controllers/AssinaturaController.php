<?php

namespace App\Http\Controllers;

use App\Models\Conta;
use App\Models\Plano;
use App\Services\ClinicaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\StripeClient;

class AssinaturaController extends Controller
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    private function contaAtiva(Request $request): Conta
    {
        return $request->user()->contaAtiva
            ?? $request->user()->clinicaAtiva?->conta
            ?? abort(404, 'Conta não encontrada.');
    }

    public function index(Request $request): Response
    {
        $conta = $this->contaAtiva($request)->load('plano');
        $planos = Plano::where('ativo', true)->orderBy('ordem')->get();

        $totalUnidades = $conta->clinicas()->count();

        $faturas = [];
        if ($conta->stripe_customer_id) {
            try {
                $invoices = $this->stripe->invoices->all([
                    'customer' => $conta->stripe_customer_id,
                    'limit'    => 12,
                ]);
                $faturas = collect($invoices->data)->map(fn($inv) => [
                    'id'      => $inv->id,
                    'numero'  => $inv->number,
                    'valor'   => $inv->amount_paid / 100,
                    'status'  => $inv->status,
                    'data'    => date('d/m/Y', $inv->created),
                    'pdf_url' => $inv->invoice_pdf,
                ])->toArray();
            } catch (\Exception) {
                // Stripe inacessível em dev — ok
            }
        }

        return Inertia::render('Planos/Index', [
            'planos' => $planos,
            'conta'  => [
                'plano_id'               => $conta->plano_id,
                'plano_slug'             => $conta->plano?->slug,
                'status_pagamento'       => $conta->status_pagamento,
                'data_vencimento'        => $conta->data_vencimento?->format('d/m/Y'),
                'data_trial_fim'         => $conta->data_trial_fim?->format('d/m/Y'),
                'stripe_subscription_id' => $conta->stripe_subscription_id,
                'total_unidades'         => $totalUnidades,
                'limite_unidades'        => $conta->plano?->clinicas_ilimitadas ? null : $conta->plano?->limite_clinicas,
            ],
            'faturas' => $faturas,
        ]);
    }

    public function checkoutStripe(Request $request): RedirectResponse
    {
        $request->validate([
            'plano_id'       => ['required', 'exists:planos,id'],
            'periodicidade'  => ['required', 'in:mensal,anual'],
        ]);

        $conta = $this->contaAtiva($request);
        $plano = Plano::findOrFail($request->plano_id);

        $priceId = $request->periodicidade === 'anual'
            ? $plano->stripe_price_anual_id
            : $plano->stripe_price_mensal_id;

        if (! $priceId) {
            return back()->with('error', 'Plano não configurado no Stripe.');
        }

        if (! $conta->stripe_customer_id) {
            $customer = $this->stripe->customers->create([
                'name'     => $conta->nome,
                'email'    => $conta->email,
                'metadata' => ['conta_id' => $conta->id],
            ]);
            $conta->update(['stripe_customer_id' => $customer->id]);
        }

        $session = $this->stripe->checkout->sessions->create([
            'customer'   => $conta->stripe_customer_id,
            'mode'       => 'subscription',
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'success_url' => url('/planos?checkout=sucesso'),
            'cancel_url'  => url('/planos?checkout=cancelado'),
            'metadata'    => ['conta_id' => $conta->id, 'plano_id' => $plano->id],
            'subscription_data' => [
                'metadata' => ['conta_id' => $conta->id, 'plano_id' => $plano->id],
            ],
        ]);

        return redirect($session->url);
    }

    public function checkoutPix(Request $request): JsonResponse
    {
        $request->validate([
            'plano_id'      => ['required', 'exists:planos,id'],
            'periodicidade' => ['required', 'in:mensal,anual'],
        ]);

        $conta = $this->contaAtiva($request);
        $plano = Plano::findOrFail($request->plano_id);

        $valor        = $request->periodicidade === 'anual' ? $plano->preco_anual : $plano->preco_mensal;
        $valorCentavos = (int) round($valor * 100);
        $correlationId = 'bullclinic-' . $conta->id . '-' . $plano->id . '-' . time();

        $response = Http::withHeaders([
            'Authorization' => config('services.woovi.api_key'),
            'Content-Type'  => 'application/json',
        ])->post(config('services.woovi.api_url') . '/api/v1/charge', [
            'correlationID' => $correlationId,
            'value'         => $valorCentavos,
            'comment'       => "BullClinic — Plano {$plano->nome} ({$request->periodicidade})",
            'customer'      => [
                'name'  => $conta->nome,
                'email' => $conta->email,
                'taxID' => ['taxID' => preg_replace('/\D/', '', $conta->cnpj ?? ''), 'type' => 'BR:CNPJ'],
            ],
            'additionalInfo' => [
                ['key' => 'conta_id',      'value' => (string) $conta->id],
                ['key' => 'plano_id',      'value' => (string) $plano->id],
                ['key' => 'periodicidade', 'value' => $request->periodicidade],
            ],
        ]);

        if (! $response->successful()) {
            return response()->json(['error' => 'Erro ao gerar cobrança PIX.'], 422);
        }

        $charge = $response->json()['charge'] ?? [];

        return response()->json([
            'pix_code'       => $charge['brCode'] ?? null,
            'qr_image_url'   => $charge['qrCodeImage'] ?? null,
            'correlation_id' => $correlationId,
            'valor'          => $valor,
            'expiracao'      => isset($charge['expiresDate'])
                ? date('d/m/Y H:i', strtotime($charge['expiresDate']))
                : null,
        ]);
    }

    public function portal(Request $request): RedirectResponse
    {
        $conta = $this->contaAtiva($request);

        if (! $conta->stripe_customer_id) {
            return back()->with('error', 'Nenhuma assinatura ativa encontrada.');
        }

        $session = $this->stripe->billingPortal->sessions->create([
            'customer'   => $conta->stripe_customer_id,
            'return_url' => url('/planos'),
        ]);

        return redirect($session->url);
    }

    public function cancelar(Request $request): RedirectResponse
    {
        $conta = $this->contaAtiva($request);

        if (! $conta->stripe_subscription_id) {
            return back()->with('error', 'Nenhuma assinatura para cancelar.');
        }

        $this->stripe->subscriptions->cancel($conta->stripe_subscription_id);

        $conta->update([
            'status_pagamento'       => 'cancelado',
            'stripe_subscription_id' => null,
        ]);

        return back()->with('success', 'Assinatura cancelada. O acesso permanece até o fim do período pago.');
    }
}

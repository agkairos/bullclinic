<?php

namespace App\Http\Controllers;

use App\Models\Conta;
use App\Models\Plano;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException) {
            return response('Assinatura inválida.', 400);
        } catch (\Exception) {
            return response('Payload inválido.', 400);
        }

        match ($event->type) {
            'checkout.session.completed'     => $this->onCheckoutCompleted($event),
            'invoice.payment_succeeded'      => $this->onInvoicePago($event),
            'invoice.payment_failed'         => $this->onInvoiceFalhou($event),
            'customer.subscription.deleted'  => $this->onSubscriptionCancelada($event),
            'customer.subscription.updated'  => $this->onSubscriptionAtualizada($event),
            default                          => null,
        };

        return response('OK', 200);
    }

    private function conta(Event $event, string $field = 'clinica_id'): ?Conta
    {
        $obj = $event->data->object;
        $contaId = $obj->metadata->conta_id ?? null;

        if ($contaId) return Conta::find($contaId);

        // Fallback: buscar pelo stripe_customer_id
        $customerId = $obj->customer ?? null;
        if ($customerId) return Conta::where('stripe_customer_id', $customerId)->first();

        return null;
    }

    private function onCheckoutCompleted(Event $event): void
    {
        $session = $event->data->object;
        $contaId = $session->metadata->conta_id ?? null;
        $planoId = $session->metadata->plano_id ?? null;

        $conta = $contaId ? Conta::find($contaId) : null;
        if (! $conta) return;

        $conta->update([
            'plano_id'                 => $planoId ?? $conta->plano_id,
            'status_pagamento'         => 'pago',
            'stripe_subscription_id'   => $session->subscription,
            'data_vencimento'          => now()->addMonth(),
        ]);

        // Atualizar plano_id nas clínicas da conta
        if ($planoId) {
            $conta->clinicas()->update(['plano_id' => $planoId]);
        }
    }

    private function onInvoicePago(Event $event): void
    {
        $invoice = $event->data->object;
        $subscriptionId = $invoice->subscription;
        if (! $subscriptionId) return;

        $conta = Conta::where('stripe_subscription_id', $subscriptionId)->first();
        if (! $conta) return;

        $periodEnd = $invoice->lines->data[0]->period->end ?? null;
        $vencimento = $periodEnd ? Carbon::createFromTimestamp($periodEnd) : now()->addMonth();

        $conta->update([
            'status_pagamento' => 'pago',
            'data_vencimento'  => $vencimento,
        ]);
    }

    private function onInvoiceFalhou(Event $event): void
    {
        $invoice = $event->data->object;
        $subscriptionId = $invoice->subscription;
        if (! $subscriptionId) return;

        Conta::where('stripe_subscription_id', $subscriptionId)
            ->first()
            ?->update(['status_pagamento' => 'atrasado']);
    }

    private function onSubscriptionCancelada(Event $event): void
    {
        $sub = $event->data->object;
        $contaId = $sub->metadata->conta_id ?? null;

        $conta = $contaId
            ? Conta::find($contaId)
            : Conta::where('stripe_subscription_id', $sub->id)->first();

        $conta?->update([
            'status_pagamento'       => 'cancelado',
            'stripe_subscription_id' => null,
        ]);
    }

    private function onSubscriptionAtualizada(Event $event): void
    {
        $sub = $event->data->object;
        $contaId = $sub->metadata->conta_id ?? null;
        $planoId = $sub->metadata->plano_id ?? null;

        $conta = $contaId
            ? Conta::find($contaId)
            : Conta::where('stripe_subscription_id', $sub->id)->first();

        if (! $conta) return;

        $updates = [
            'stripe_subscription_id' => $sub->id,
            'status_pagamento'       => match ($sub->status) {
                'active'   => 'pago',
                'past_due' => 'atrasado',
                'canceled' => 'cancelado',
                'trialing' => 'trial',
                default    => $conta->status_pagamento,
            },
        ];

        if ($planoId) {
            $updates['plano_id'] = $planoId;
            $conta->clinicas()->update(['plano_id' => $planoId]);
        }

        $conta->update($updates);
    }
}

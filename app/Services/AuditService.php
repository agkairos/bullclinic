<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public static function log(
        string $acao,
        ?Model $model = null,
        ?array $dadosAntes = null,
        ?array $dadosDepois = null,
    ): void {
        $request = app(Request::class);
        $user = auth()->user();

        AuditLog::create([
            'clinica_id' => $user?->clinica_ativa_id,
            'user_id' => $user?->id,
            'acao' => $acao,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->getKey(),
            'dados_antes' => $dadosAntes,
            'dados_depois' => $dadosDepois,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'criado_em' => now(),
        ]);
    }

    public static function criado(Model $model): void
    {
        static::log('criado', $model, null, $model->toArray());
    }

    public static function editado(Model $model, array $dadosAntes): void
    {
        static::log('editado', $model, $dadosAntes, $model->toArray());
    }

    public static function excluido(Model $model): void
    {
        static::log('excluido', $model, $model->toArray(), null);
    }
}

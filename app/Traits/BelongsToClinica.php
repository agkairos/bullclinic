<?php

namespace App\Traits;

use App\Services\ClinicaContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToClinica
{
    protected static function bootBelongsToClinica(): void
    {
        static::addGlobalScope('clinica', function (Builder $builder) {
            $clinicaId = ClinicaContext::id();
            if ($clinicaId) {
                $builder->where($builder->getModel()->getTable() . '.clinica_id', $clinicaId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->clinica_id)) {
                $model->clinica_id = ClinicaContext::id();
            }
        });
    }

    public function scopeDeClinica(Builder $query, int $clinicaId): Builder
    {
        return $query->withoutGlobalScope('clinica')->where('clinica_id', $clinicaId);
    }
}

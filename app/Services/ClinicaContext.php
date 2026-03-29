<?php

namespace App\Services;

class ClinicaContext
{
    private static ?int $clinicaId = null;

    public static function set(int $id): void
    {
        self::$clinicaId = $id;
    }

    public static function id(): ?int
    {
        if (self::$clinicaId) {
            return self::$clinicaId;
        }

        if (auth()->check()) {
            return auth()->user()->clinica_ativa_id;
        }

        return null;
    }

    public static function clear(): void
    {
        self::$clinicaId = null;
    }
}

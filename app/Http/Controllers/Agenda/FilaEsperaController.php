<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use Inertia\Inertia;
use Inertia\Response;

class FilaEsperaController extends Controller
{
    public function index(): Response
    {
        $fila = Agendamento::with(['paciente.fotos', 'cliente', 'veterinario', 'tipoServico'])
            ->whereDate('data', today())
            ->where('status', 'aguardando')
            ->orderBy('checkin_em')
            ->get();

        return Inertia::render('Agenda/FilaEspera', ['fila' => $fila]);
    }
}

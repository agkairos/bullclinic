<?php

namespace App\Http\Controllers\Prontuario;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\ReceitaMedica;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ProntuarioPdfController extends Controller
{
    /** GET /prontuarios/paciente/{paciente}/pdf */
    public function prontuario(Paciente $paciente): Response
    {
        $paciente->load([
            'cliente',
            'consultas' => fn($q) => $q->with(['veterinario', 'receitas.itens', 'procedimentos', 'vacinacoes'])->latest()->limit(50),
            'vacinacoes' => fn($q) => $q->latest('data_aplicacao'),
        ]);

        $pdf = Pdf::loadView('pdf.prontuario', ['paciente' => $paciente])
            ->setPaper('a4', 'portrait');

        return $pdf->download("prontuario-{$paciente->nome}.pdf");
    }

    /** GET /receitas/{receita}/pdf */
    public function receita(ReceitaMedica $receita): Response
    {
        $receita->load(['itens', 'paciente.cliente', 'veterinario']);

        $pdf = Pdf::loadView('pdf.receita', ['receita' => $receita])
            ->setPaper('a4', 'portrait');

        return $pdf->download("receita-{$receita->paciente->nome}-{$receita->id}.pdf");
    }
}

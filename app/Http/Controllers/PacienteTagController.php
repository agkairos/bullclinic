<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PacienteTagController extends Controller
{
    /**
     * Sincroniza as tags do paciente.
     * Recebe: { tags: ["tag1", "tag2", ...] }
     */
    public function sync(Request $request, Paciente $paciente): JsonResponse
    {
        $request->validate([
            'tags'   => ['required', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        // Remove duplicatas e limpa espaços
        $tags = collect($request->tags)
            ->map(fn($t) => trim($t))
            ->filter()
            ->unique()
            ->values();

        $paciente->tags()->delete();

        foreach ($tags as $tag) {
            $paciente->tags()->create(['tag' => $tag]);
        }

        return response()->json(['tags' => $paciente->tags()->pluck('tag')]);
    }
}

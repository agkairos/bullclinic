<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);

        // Apenas renderiza páginas customizadas para requests Inertia
        if (!$request->header('X-Inertia')) {
            return $response;
        }

        // Verifica se é uma HttpException (403, 404, 500, etc)
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
        } else {
            $status = $response->getStatusCode();
        }

        // Renderiza páginas customizadas para códigos específicos
        if (in_array($status, [403, 404, 500, 503])) {
            return Inertia::render("Errors/{$status}", [
                'status' => $status,
                'message' => $this->getErrorMessage($e, $status),
            ])
            ->toResponse($request)
            ->setStatusCode($status);
        }

        return $response;
    }

    /**
     * Obtém a mensagem de erro apropriada.
     */
    protected function getErrorMessage(Throwable $e, int $status): string
    {
        // Se a exception tem mensagem customizada, usa ela
        if ($e->getMessage() && !str_contains($e->getMessage(), 'The')) {
            return $e->getMessage();
        }

        // Mensagens padrão
        return match ($status) {
            403 => 'Você não tem permissão para acessar esta página.',
            404 => 'A página que você está procurando não existe ou foi movida.',
            500 => 'Ocorreu um erro inesperado. Nossa equipe foi notificada e está trabalhando na correção.',
            503 => 'O sistema está temporariamente indisponível. Tente novamente em alguns instantes.',
            default => 'Ocorreu um erro. Por favor, tente novamente.',
        };
    }
}

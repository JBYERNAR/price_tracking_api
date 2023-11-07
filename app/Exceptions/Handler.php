<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;

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

    public function render($request, Throwable $e)
    {
        if ($e instanceof ModelNotFoundException) {
            return $this->errorResponse(
                null,
                Response::HTTP_NOT_FOUND,
                $e->getMessage()
            );
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->errorResponse(
                null,
                Response::HTTP_NOT_FOUND,
                'Маршрут не найден'
            );
        }

        Log::error($e->getMessage());

        return parent::render($request, $e);
    }
}

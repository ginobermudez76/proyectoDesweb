<?php

namespace App\Shared\Traits;

use App\Shared\HttpStatus\HttpStatus;
use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    /**
     * Retorna una respuesta exitosa estandarizada.
     */
    protected function successResponse(mixed $data = null, string|HttpStatus $message = HttpStatus::OK, int|HttpStatus $code = HttpStatus::OK): JsonResponse
    {
        $statusCode = $code instanceof HttpStatus ? $code->value : $code;
        $statusMessage = $message instanceof HttpStatus ? $message->label() : $message;

        return response()->json([
            'status' => 'success',
            'message' => $statusMessage,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Retorna una respuesta de error estandarizada.
     *
     * @param  array<string, mixed>  $details
     */
    protected function errorResponse(string|HttpStatus $message, int|HttpStatus $code, array $details = []): JsonResponse
    {
        $statusCode = $code instanceof HttpStatus ? $code->value : $code;
        $statusMessage = $message instanceof HttpStatus ? $message->label() : $message;
        $description = $message instanceof HttpStatus ? $message->description() : '';

        $responseBody = [
            'status' => 'error',
            'message' => $statusMessage,
        ];

        if (!empty($description)) {
            $responseBody['description'] = $description;
        }

        if (!empty($details)) {
            $responseBody['details'] = $details;
        }

        return response()->json($responseBody, $statusCode);
    }
}

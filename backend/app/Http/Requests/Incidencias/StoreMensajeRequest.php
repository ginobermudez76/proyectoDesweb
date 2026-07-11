<?php

namespace App\Http\Requests\Incidencias;

use Illuminate\Foundation\Http\FormRequest;

class StoreMensajeRequest extends FormRequest
{
    public function authorize(): bool
    {

        return true;
    }

    public function rules(): array
    {
        return [
            'contenido' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'contenido.required' => 'El mensaje no puede estar vacío.',
            'contenido.string' => 'El mensaje debe ser un texto válido.',
            'contenido.max' => 'El mensaje es demasiado largo (máximo 1000 caracteres).',
        ];
    }
}

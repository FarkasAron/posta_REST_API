<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // PATCH esetén elég a módosítandó mezőket elküldeni.
        $required = $this->isMethod('PATCH') ? 'sometimes|required' : 'required';

        return [
            'name' => $required.'|string|min:3|max:50',
            'id_county' => $required.'|integer|exists:counties,id',
            'zip_code' => $required.'|integer|between:1000,9999',
            'population' => $required.'|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A város neve kötelező.',
            'name.min' => 'A város neve legalább 3 karakter hosszú legyen.',
            'name.max' => 'A város neve legfeljebb 50 karakter hosszú lehet.',
            'id_county.required' => 'A megye megadása kötelező.',
            'id_county.exists' => 'A megadott megye nem létezik.',
            'zip_code.required' => 'Az irányítószám kötelező.',
            'zip_code.between' => 'Az irányítószám 1000 és 9999 közötti szám legyen.',
            'population.required' => 'A lakosságszám kötelező.',
            'population.min' => 'A lakosságszám nem lehet negatív.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CountyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('PATCH') ? 'sometimes|required' : 'required';

        return [
            'name' => $required.'|string|min:3|max:50',
            'badge' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A megye neve kötelező.',
            'name.min' => 'A megye neve legalább 3 karakter hosszú legyen.',
            'name.max' => 'A megye neve legfeljebb 50 karakter hosszú lehet.',
        ];
    }
}

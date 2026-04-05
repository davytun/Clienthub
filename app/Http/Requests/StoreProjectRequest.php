<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth enforced by middleware; business scope enforced in controller
    }

    public function rules(): array
    {
        return [
            'client_id'   => ['required', 'exists:users,id'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status'      => ['required', 'in:active,on_hold,completed'],
        ];
    }
}

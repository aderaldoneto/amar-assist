<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document' => preg_replace(
                '/\D/',
                '',
                (string) $this->input('document')
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => [
                'required',
                'regex:/^(\d{11}|\d{14})$/',
                Rule::unique(Client::class, 'document'),
            ],
            'address' => ['required', 'string', 'max:1000'],
            'contact' => ['required', 'string', 'max:255'],
            'status' => [
                'sometimes',
                Rule::in([
                    Client::STATUS_ACTIVE,
                    Client::STATUS_INACTIVE,
                ]),
            ],
        ];
    }
}
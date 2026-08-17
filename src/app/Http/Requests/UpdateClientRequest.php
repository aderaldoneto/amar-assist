<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('document')) {
            $this->merge([
                'document' => preg_replace(
                    '/\D/',
                    '',
                    (string) $this->input('document')
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'document' => [
                'sometimes',
                'required',
                'regex:/^(\d{11}|\d{14})$/',
                Rule::unique(Client::class, 'document')
                    ->ignore($this->route('client')),
            ],
            'address' => [
                'sometimes',
                'required',
                'string',
                'max:1000',
            ],
            'contact' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::in([
                    Client::STATUS_ACTIVE,
                    Client::STATUS_INACTIVE,
                ]),
            ],
        ];
    }
}
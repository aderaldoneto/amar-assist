<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('type')) {
            $this->merge([
                'type' => strtoupper(
                    trim((string) $this->input('type'))
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id'),
            ],
            'type' => [
                'required',
                Rule::in([
                    Contract::TYPE_PF,
                    Contract::TYPE_PJ,
                ]),
            ],
            'billing_day' => [
                'required',
                'integer',
                'between:1,31',
            ],
        ];
    }
}
<?php

namespace App\Http\Requests;

use App\Models\Charge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('payment_method')) {
            $this->merge([
                'payment_method' => strtolower(
                    trim((string) $this->input('payment_method'))
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'contract_id' => [
                'required',
                'integer',
                Rule::exists('contracts', 'id'),
            ],
            'payment_method' => [
                'required',
                Rule::in([
                    Charge::METHOD_BOLETO,
                    Charge::METHOD_CARD,
                    Charge::METHOD_PIX,
                ]),
            ],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'regex:/^\d{1,10}(\.\d{1,2})?$/',
            ],
            'reference_month' => [
                'required',
                'date_format:Y-m',
            ],

            'barcode' => [
                'required_if:payment_method,boleto',
                'nullable',
                'string',
                'max:255',
            ],
            'pix_key' => [
                'required_if:payment_method,pix',
                'nullable',
                'string',
                'max:255',
            ],
            'card_holder_name' => [
                'required_if:payment_method,card',
                'nullable',
                'string',
                'max:255',
            ],
            'card_brand' => [
                'required_if:payment_method,card',
                'nullable',
                'string',
                'max:30',
            ],
            'card_last_four' => [
                'required_if:payment_method,card',
                'nullable',
                'regex:/^\d{4}$/',
            ],
        ];
    }
}
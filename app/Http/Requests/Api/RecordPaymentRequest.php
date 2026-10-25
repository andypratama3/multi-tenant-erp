<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'paid_at' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,transfer,qris,cheque,other'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

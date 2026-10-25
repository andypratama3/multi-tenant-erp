<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|string',
            'paid_at' => 'required|date',
            'payment_method' => 'required|in:cash,transfer,cheque,other',
            'reference' => 'nullable|string|max:100',
        ];
    }
}

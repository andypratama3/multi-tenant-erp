<?php

namespace App\Http\Requests\Api;

use App\Domain\Sales\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');
        $customerId = $customer instanceof Customer ? $customer->id : null;

        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('customers', 'code')->ignore($customerId)],
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'tax_id' => ['nullable', 'string', 'max:30'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:365'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

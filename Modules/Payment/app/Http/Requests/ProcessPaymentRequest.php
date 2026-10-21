<?php

namespace Modules\Payment\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseFormRequest;
use Modules\Payment\Enums\PaymentMethod;

class ProcessPaymentRequest extends BaseFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'payload' => ['sometimes', 'array'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'method.required' => 'A payment method is required.',
            'method.enum' => "The payment method ':input' is not supported. Supported methods: "
                .implode(', ', PaymentMethod::values()).'.',
            'payload.array' => 'The payment payload must be an object.',
        ];
    }
}

<?php

namespace Modules\Payment\Http\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseFormRequest;
use Modules\Payment\Enums\PaymentGateway;
use Modules\Payment\Enums\PaymentMethod;

#[BodyParameter(
    'payload.simulate_outcome',
    description: 'Controls the deterministic simulated gateway result.',
    type: "'success'|'declined'",
    example: 'success',
)]
class ProcessPaymentRequest extends BaseFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'gateway' => ['required', Rule::enum(PaymentGateway::class)],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'payload' => ['sometimes', 'array'],
            'payload.simulate_outcome' => ['sometimes', Rule::in(['success', 'declined'])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'gateway.required' => 'A payment gateway is required.',
            'gateway.enum' => "The payment gateway ':input' is not supported. Supported gateways: "
                .implode(', ', PaymentGateway::values()).'.',
            'method.required' => 'A payment method is required.',
            'method.enum' => "The payment method ':input' is not supported. Supported methods: "
                .implode(', ', PaymentMethod::values()).'.',
            'payload.array' => 'The payment payload must be an object.',
            'payload.simulate_outcome.in' => 'The simulation outcome must be either success or declined.',
        ];
    }
}

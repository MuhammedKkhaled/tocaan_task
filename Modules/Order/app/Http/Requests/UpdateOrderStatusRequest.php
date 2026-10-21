<?php

namespace Modules\Order\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\BaseFormRequest;
use Modules\Order\Enums\OrderStatus;

class UpdateOrderStatusRequest extends BaseFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.required' => 'A status is required.',
            'status.enum' => 'Status must be one of: '.implode(', ', OrderStatus::values()).'.',
        ];
    }
}

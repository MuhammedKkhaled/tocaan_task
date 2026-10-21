<?php

namespace Modules\Order\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Enums\Currency;
use Modules\Core\Http\Requests\BaseFormRequest;

class StoreOrderRequest extends BaseFormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
            'currency' => ['sometimes', Rule::enum(Currency::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'currency.enum' => 'Currency must be one of: '.implode(', ', Currency::values()).'.',
            'items.required' => 'An order must contain at least one item.',
            'items.min' => 'An order must contain at least one item.',
            'items.*.product_name.required' => 'Each item needs a product name (item #:position is missing one).',
            'items.*.quantity.required' => 'Please provide a quantity for item #:position.',
            'items.*.quantity.min' => 'Quantity for item #:position must be at least 1.',
            'items.*.price.required' => 'Please provide a price for item #:position.',
            'items.*.price.decimal' => 'Price for item #:position may have at most two decimal places.',
            'items.*.price.min' => 'Price for item #:position must be greater than zero.',
        ];
    }
}

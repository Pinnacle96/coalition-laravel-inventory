<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryEntryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'quantity' => ['required', 'integer', 'between:0,999999'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'between:0,999999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'product name',
            'quantity' => 'quantity in stock',
            'price' => 'price per item',
        ];
    }

    public function entry(): array
    {
        $data = $this->validated();
        [$whole, $fraction] = array_pad(explode('.', (string) $data['price'], 2), 2, '');

        return [
            'name' => $data['name'],
            'quantity' => (int) $data['quantity'],
            'price_minor' => (int) $whole * 100 + (int) str_pad($fraction, 2, '0'),
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|string|max:100',
            'status' => 'required|string|in:pending,completed,refunded,cancelled',
            'order_date' => 'required|date',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . ($this->route('customer') ?? 'NULL'),
            'phone' => 'required|string|max:20',
            'city' => 'required|string|max:100',
            'gender' => 'required|string|max:10',
            'date_of_birth' => 'required|date',
            'engagement_score' => 'nullable|integer|min:0|max:100',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            // product
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'unit_price' => 'required|decimal:2|gt:0',
            // categories_product categories id
            'categories' => 'required|array',
            'categories.*' => 'numeric|gt:0|decimal:0|max:255',
            // product_warehouse quantity
            'quantity' => 'required|decimal:0|gte:0|lte:4294967295',
        ];
    }

    /**
     * Returns only product validation rules.
     */
    public function validatedProduct(): array
    {
        return $this->only('name', 'description', 'unit_price');
    }

    /**
     * Returns only categories id validation rules.
     */
    public function validatedCategoriesId(): array
    {
        return $this->only('categories')['categories'];
    }

    /**
     * Returns only quantity validation rules.
     */
    public function validatedQuantity(): array
    {
        return $this->only('quantity');
    }
}

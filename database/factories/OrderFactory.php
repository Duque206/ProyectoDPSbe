<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $subTotal = fake()->randomFloat(2, 5, 500);
        $shipping = fake()->randomFloat(2, 1, 10);
        $taxes = fake()->randomFloat(2, 1, 20);

        return [
            'user_id' => 1,
            'item_count' => fake()->numberBetween(1, 20),
            'sub_total' => $subTotal,
            'shipping_cost' => $shipping,
            'taxes' => $taxes,
            'total' => $subTotal + $shipping + $taxes,
            'finished' => true,
            'canceled' => false,
        ];
    }
}

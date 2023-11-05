<?php

namespace Database\Seeders;

use App\Models\LineItem;
use App\Models\Order;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Order::factory(25)->create()->each(function ($order) {
            $sub_total = 0;
            $item_count = 0;

            $line_items = LineItem::factory(rand(1, 10))->create();
            foreach ($line_items as $item) {
                $item->order_id = $order->id;
                $sub_total += $item->item_total;
                $item_count += $item->quantity;
                $item->save();
            }

            $order->user_id = 8;
            $order->item_count = $item_count;
            $order->sub_total = $sub_total;
            $order->taxes = round($sub_total * 0.10, 2);
            $order->total = $order->shiping_cost + $order->taxes + $sub_total;
            $order->save();
        });
    }
}

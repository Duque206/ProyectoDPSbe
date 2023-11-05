<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentOrder = $request->user()->orders()->latest()->with(['line_items' => ['product'],'user'])->first();
        if ($currentOrder == null || $currentOrder->finished) {
            $currentOrder = null;
        } else {
            $this->calculateOrder($currentOrder);
        }

        $search = request("search");
        $orders = Order::when($search ?? false, function($query, $search) {
            $search = preg_replace("/([^0-9\s])+/i", "", $search);
            $query->where('id', 'LIKE', "%$search%");
        })->when($currentOrder ?? false, function ($query, $currentOrder) {
            $query->where('id', '!=', $currentOrder->id);
        })->where('finished', true)->paginate(15)->withQueryString();

        return response()->json([
            'currentOrder' => $currentOrder,
            'orders' => $orders->toArray()['data'],
            'links' => $orders->toArray()['links'],
            'filters' => request()->only(['search'])
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(['line_items' => ['product'], 'user']);
        $order->line_items->map(function($item){
            return $item->product->append('quantity')->makeHidden('warehouses');
        });
        return response()->json([
            'order' => $order,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->load(['line_items' => ['product']]);
        if ($order->finished) {
            return response()->json([
                'message' => 'Order already finished, cannot be erased',
            ]);
        }

        $order->line_items->each(function ($item, $key) use ($order) {
            $item->product->warehouses()->sync([Auth::user()->warehouse->id => [
                'quantity' => $item->product->quantity + $item->quantity,
            ]]);
            $item->delete();
        });

        $order->item_count = 0;
        $order->save();

        return response()->json([
            'id' => $order->id,
            'message' => 'Order erased successfully',
        ]);
    }

    public function finishOrder(Order $order)
    {
        $order->load(['line_items' => ['product']]);
        if ($order->finished) {
            return response()->json([
                'message' => 'Not order available to finish',
            ]);
        }

        $this->calculateOrder($order);
        $order->finished = true;
        $order->save();

        return response()->json([
            'id' => $order->id,
            'message' => 'Order finished correctly',
        ]);
    }

    public function cancelOrder(Order $order)
    {
        $order->load(['line_items' => ['product']]);

        $order->line_items->each(function ($item, $key) use ($order) {
            $item->product->warehouses()->sync([Auth::user()->warehouse->id => [
                'quantity' => $item->product->quantity + $item->quantity,
            ]]);
        });

        $order->canceled = true;
        $order->save();

        return response()->json([
            'type' => 'floating',
            'message' => 'Order canceled correctly',
            'level' => 'success'
        ]);
    }

    protected function calculateOrder(Order $order)
    {
        $order->line_items->map(function($item){
            return $item->product->append('quantity')->makeHidden('warehouses');
        });
        $order->sub_total = round($order->line_items->sum('item_total'), 2);
        $order->shipping_cost = round(($order->sub_total * .01) * $order->item_count, 2);
        $order->taxes = round($order->sub_total * 0.1, 2);
        $order->total = round($order->sub_total + $order->shipping_cost + $order->taxes, 2);
    }
}

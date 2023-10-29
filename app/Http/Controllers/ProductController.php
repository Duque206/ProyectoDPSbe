<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\LineItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    protected function resourceAbilityMap(): array
    {
        return array_merge(parent::resourceAbilityMap(), [
            // method in Controller => method in Policy
            'remove' => 'remove',
            'restore' => 'restore',
            'addToOrder' => 'order',
            'removeFromOrder' => 'order',
        ]);
    }

    protected function resourceMethodsWithoutModels(): array
    {
        return array_merge(parent::resourceMethodsWithoutModels(), [
            // method in Controller
            'addToOrder',
            'removeFromOrder',
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = request("search");
        $category = request("category");
        $deleted = filter_var(request("deleted"), FILTER_VALIDATE_BOOLEAN);

        if (filter_var(request("all"), FILTER_VALIDATE_BOOLEAN) || $request->user()->role->type == 'director') {
            $products = Product::query();
        } else {
            $products = $request->user()->warehouse->products();
        }

        //$products = filter_var(request("all"), FILTER_VALIDATE_BOOLEAN) ? Product::query() : Auth::user()->warehouse->products();
        $products = $products->when($search ?? false, function($query, $search) {
            $search = preg_replace("/([^A-Za-z0-9\s])+/i", "", $search);
            $query->where('name', 'LIKE', "%$search%");
        })->when($category ?? false, function ($query, $category) {
            if ($category != 'all') {
                $query->whereRelation('categories', 'id', $category);
            }
        })->when($deleted ?? false, function ($query, $deleted) {
            if ($deleted) {
                $query->onlyTrashed();
            }
        })->with('warehouses')->paginate(15)->withQueryString();

        return response()->json([
            'links' => $products->toArray()['links'],
            'warehouse' => $request->user()->warehouse,
            'categories' => Category::all(),
            'products' => $products->map(function($product){
                return $product->append('quantity');
            }),
            'filters' => request()->only(['search', 'all', 'category', 'deleted']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $product = Product::create($request->validatedProduct());

        $product->categories()->attach($request->validatedCategoriesId());

        if ($request->user()->role->type != 'director') {
            $product->warehouses()->attach($request->user()->warehouse->id, $request->validatedQuantity());
        }

        return response()->json([
            'id' => $product->id,
            'message' => 'Product created successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Product $product)
    {
        return response()->json([
            'product' => $product->makeVisible('description')->append('quantity')->load('warehouses', 'categories'),
            'warehouse_id' => $request->user()->warehouse?->id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product)
    {
        $product->fill($request->validatedProduct());
        $product->save();

        $product->categories()->sync($request->validatedCategoriesId());

        if($product->warehouses->where('id',$request->user()->warehouse->id)->first()!=null)
            $product->warehouses()->updateExistingPivot($request->user()->warehouse->id, $request->validatedQuantity());
        else if ($request->validatedQuantity()['quantity'] > 0)
            $product->warehouses()->attach($request->user()->warehouse->id, $request->validatedQuantity());

        return response()->json([
            'id' => $product->id,
            'message' => 'Product updated',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Product $product)
    {
        $product->delete();
        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Remove a warehouse from specified resource.
     */
    public function remove(Request $request, Product $product)
    {
        $product->warehouses()->detach($request->user()->warehouse->id);
        return response()->json([
            'message' => 'Product removed from this warehouse',
        ]);
    }

    public function restore(Product $product)
    {
        $product->restore();
        return response()->json([
            'message' => 'Product restored successfully',
        ]);
    }

    public function addToOrder(Request $request, Product $product)
    {
        $quantity = request('quantity');
        if ($product->quantity < $quantity) {
            return back()->with([
                'type' => 'floating',
                'message' => 'Not enough quantity available',
                'level' => 'warning'
            ]);
        }
        $product->warehouses()->sync([$request->user()->warehouse->id => [
            'quantity' => $product->quantity - $quantity,
        ]]);

        if ($request->user()->role->type == 'employee') {
            $order = $request->user()->orders()->latest()->first();
            if ($order == null || $order->finished) {
                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'item_count' => 0,
                    'sub_total' => 0,
                    'shipping_cost' => 0,
                    'taxes'  => 0,
                    'total' => 0,
                    'canceled' => 0,
                    'finished' => false,
                ]);
            }
        } else {
            $order = Order::where('id', request('orderID'))->first();
        }


        $item = LineItem::firstOrNew(['order_id' => $order->id, 'product_id' => $product->id]);
        $item->quantity += $quantity;
        $item->unit_price = $product->unit_price;
        $item->save();

        $order->item_count += $quantity;
        $order->save();

        return response()->json([
            'order_id' => $order->id,
            'message' => 'Product added to order successfully'
        ]);
    }

    public function removeFromOrder(Request $request, Product $product)
    {
        if ($request->user()->role->type == 'employee') {
            $order = $request->user()->orders()->latest()->first();
            if ($order == null || $order->finished) {
                return back()->with([
                    'type' => 'floating',
                    'message' => 'No order available',
                    'level' => 'warning'
                ]);
            }
        } else {
            $order = Order::where('id', request('orderID'))->first();
        }

        $quantity = request('quantity');
        $item = LineItem::where('order_id', $order->id)->where('product_id', $product->id)->first();

        if($item == null) {
            return back()->with([
                'type' => 'floating',
                'message' => 'Product on order does not exists',
                'level' => 'warning'
            ]);
        }

        if ($quantity >= $item->quantity) {
            $quantity = $item->quantity;
            $item->delete();
        } else {
            $item->quantity -= $quantity;
            $item->save();
        }

        $product->warehouses()->sync([$request->user()->warehouse->id => [
            'quantity' => $product->quantity + $quantity,
        ]]);

        $order->item_count -= $quantity;
        $order->save();

        return response()->json([
            'message' => $item->exists ? 'Quantity removed from the order successfully' : 'Product removed from order successfully',
        ]);
    }
}

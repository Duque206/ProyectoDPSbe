<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Warehouse::class, 'warehouse');
    }

    protected function resourceAbilityMap(): array
    {
        return array_merge(parent::resourceAbilityMap(), [
            'restore' => 'restore',
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = request("search");
        $deleted = filter_var(request("deleted"), FILTER_VALIDATE_BOOLEAN);

        $warehouses = Warehouse::query()->when($search ?? false, function ($query, $search) {
            $search = preg_replace("/([^A-Za-z0-9\s])+/i", "", $search);
            $query->where('name', 'LIKE', "%$search%");
        })->when($deleted ?? false, function ($query, $deleted) {
            if ($deleted) {
                $query->onlyTrashed();
            }
        })->paginate(15)->withQueryString();

        return response()->json([
            'warehouses' => $warehouses->toArray()['data'],
            'links' => $warehouses->toArray()['links'],
            'filters' => request()->only(['search', 'deleted']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WarehouseRequest $request)
    {
        $warehouse = Warehouse::create($request->validated());

        return response()->json([
            'id' => $warehouse->id,
            'message' => 'Warehouse created successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse)
    {
        return response()->json([
            'warehouse' => $warehouse,
            'staff' => $warehouse->users->load(['role']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        $warehouse->update($request->validated());

        return response()->json([
            'id' => $warehouse->id,
            'message' => 'Warehouse updated',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Warehouse $warehouse)
    {
        $warehouse->delete();

        if ($warehouse->id == Auth::user()->warehouse?->id) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Warehouse disabled successfully'
        ]);
    }

    public function restore(Warehouse $warehouse)
    {
        $warehouse->restore();
        return response()->json([
            'message' => 'Warehouse restored successfully',
        ]);
    }
}

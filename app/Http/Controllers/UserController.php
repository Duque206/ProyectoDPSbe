<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = request('search');
        $deleted = filter_var(request("deleted"), FILTER_VALIDATE_BOOLEAN);

        if (filter_var(request("all"), FILTER_VALIDATE_BOOLEAN) || $request->user()->role->type == 'director') {
            $users = User::query();
        } else {
            $users = $request->user()->warehouse->users();
        }

        $users = $users->when($search ?? false, function($query, $search){
            $search = preg_replace("/([^A-Za-z0-9\s])+/i", "", $search);
            $query->where('name', 'LIKE', "%$search%");
        })->when($deleted ?? false, function ($query, $deleted) {
            if ($deleted) {
                $query->onlyTrashed();
            }
        })->with('warehouse', 'role')->paginate(15)->withQueryString();

        return response()->json([
            'links' => $users->toArray()['links'],
            'users' => $users->toArray()['data'],
            'filters' => request()->only(['search', 'all', 'deleted']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $user = User::create($request->validated());
        return response()->json([
            'id' => $user->id,
            'message' => 'User created successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json([
            'user' => $user->load(['role', 'warehouse']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $user->update($request->validated());
        return response()->json([
            'id' => $user->id,
            'message' => 'User updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}

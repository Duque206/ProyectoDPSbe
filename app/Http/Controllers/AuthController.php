<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request){
        $atributos = $request->validate([
            'email' => 'required|email|max:100',
            'password' => 'required|string|max:100'
        ]);
        if(Auth::attempt($atributos)){
            return response()->json([
                'token' => $request->user()->createToken($request->email)->plainTextToken,
                'user' => $request->user()->load(['role', 'warehouse'])
            ]);
        }
        return response()->json([
            'message' => 'Incorrect email or password'
        ],401);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Sesion Terminada'
        ]);
    }
}

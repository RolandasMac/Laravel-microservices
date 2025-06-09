<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'email'            => 'required|email|unique:users',
            'password'         => 'required',
            'name'             => 'required',
            'confirm_password' => 'required|same:password',
        ]);
        $user = $request->all();
        // $newUser = User::create(['name' => $user['name'], 'email' => $user['email'], 'password' => $user['password']]);
        // $newUser->save();

        $user = User::create($request->all());

        return response()->json(['message' => 'User created successfully', 'user' => $user], status: 201);
    }
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Wrong credentials'], status: 401);
        }
        $token = $user->createToken('apitoken')->plainTextToken;
        return response()->json(['message' => 'Logged in successfully!', 'user' => $user, 'token' => $token], status: 200);
    }
    public function logout(Request $request)
    {
        return response()->json(['message' => 'Logged out successfully'], status: 200);
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully'], status: 200);
    }
}

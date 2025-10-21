<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $req)
    {
        $username = strtolower(trim($req->username));
        $user = User::where('username', $username)->first();

        if (!$user || !Hash::check($req->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // revoke old tokens if you want one-session-per-user
        // $user->tokens()->delete();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'          => $user->id,
                'username'    => $user->username,
                'role'        => $user->role,
                'id_fakultas' => $user->id_fakultas,
                'id_prodi'    => $user->id_prodi,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('fakultas:id,nama_fakultas','prodi:id,id_fakultas,nama_prodi'));
    }

    public function logout(Request $request)
    {
        // Revoke current token only:
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['ok' => true]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;      // <— tambahkan
use Illuminate\Support\Facades\Schema;  // <— tambahkan

class AuthController extends Controller
{
    public function login(LoginRequest $req)
    {
        $username = strtolower(trim($req->username));
        $user = User::where('username', $username)->first();

        if (!$user || !Hash::check($req->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

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

    public function loginByNim(Request $req)
    {
        // Validasi input
        $data = $req->validate([
            'nim' => ['required', 'string', 'max:20'],
        ]);
        $nim = trim($data['nim']);

        // Cari di ref_mahasiswa
        $mhs = DB::table('ref_mahasiswa')->where('nim', $nim)->first();
        if (!$mhs) {
            return response()->json(['message' => 'NIM tidak ditemukan'], 401);
        }

        // Ambil id_prodi (boleh null). Cari id_fakultas dari ref_prodi jika ada
        $idProdi = $mhs->id_prodi ?? null;
        $idFak   = null;
        if (!empty($idProdi)) {
            $idFak = DB::table('ref_prodi')->where('id', $idProdi)->value('id_fakultas');
        }

        // Pastikan ENUM role memuat 'Mahasiswa'
        if (Schema::hasColumn('users', 'role')) {
            try {
                $enumType = DB::table('information_schema.COLUMNS')
                    ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                    ->where('TABLE_NAME', 'users')
                    ->where('COLUMN_NAME', 'role')
                    ->value('COLUMN_TYPE');
                if (is_string($enumType) && !str_contains($enumType, "'Mahasiswa'")) {
                    return response()->json(['message' => "Kolom users.role belum memuat 'Mahasiswa'"], 500);
                }
            } catch (\Throwable $e) {
                // abaikan cek enum kalau gagal
            }
        }

        // JIT provision/update user
        $user = User::updateOrCreate(
            ['username' => $nim], // kunci unik
            [
                'role'        => 'Mahasiswa',
                'password'    => Hash::make($nim), // set password = nim
                'id_prodi'    => $idProdi ?: null, // pakai null agar FK aman
                'id_fakultas' => $idFak,           // bisa null
                'nim'         => $nim,
            ]
        );

        // Token Sanctum
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
        return response()->json(
            $request->user()->load('fakultas:id,nama_fakultas','prodi:id,id_fakultas,nama_prodi')
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['ok' => true]);
    }
}

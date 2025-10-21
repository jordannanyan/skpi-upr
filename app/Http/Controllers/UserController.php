<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // GET /api/users?role=&q=&fakultas_id=&prodi_id=&per_page=
    public function index()
    {
        $perPage = (int) request('per_page', 25);
        $q = User::query()
            ->with(['fakultas:id,nama_fakultas', 'prodi:id,id_fakultas,nama_prodi']);

        if ($role = request('role'))        $q->where('role', $role);
        if ($fid  = request('fakultas_id')) $q->where('id_fakultas', (int)$fid);
        if ($pid  = request('prodi_id'))    $q->where('id_prodi', (int)$pid);

        if ($kw = trim((string)request('q', ''))) {
            $q->where(function ($w) use ($kw) {
                $w->where('username', 'like', "%{$kw}%")
                    ->orWhere('role', 'like', "%{$kw}%");
            });
        }

        $q->orderBy('role')->orderBy('username');

        return response()->json(
            $q->paginate($perPage)->appends(request()->query())
        );
    }

    // POST /api/users
    public function store(UserStoreRequest $req)
    {
        $data = $req->validated();
        $user = User::create($data); // password di-hash via mutator
        return response()->json($user->load('fakultas', 'prodi'), 201);
    }

    // GET /api/users/{id}
    public function show($id)
    {
        $user = User::with(['fakultas', 'prodi'])->findOrFail($id);
        return response()->json($user);
    }

    // PUT/PATCH /api/users/{id}
    public function update(UserUpdateRequest $req, $id)
    {
        $user = User::findOrFail($id);
        $data = $req->validated();
        // Jika password tidak dikirim atau kosong, jangan sentuh
        if (array_key_exists('password', $data) && !$data['password']) {
            unset($data['password']);
        }
        $user->update($data);
        return response()->json($user->load('fakultas', 'prodi'));
    }

    // DELETE /api/users/{id}
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        // Cegah hapus diri sendiri (opsional)
        if (Auth::check() && (int) Auth::id() === (int) $user->id) {
            return response()->json(['message' => 'Cannot delete self'], 422);
        }

        $user->delete();
        return response()->json(['deleted' => true]);
    }

    // POST /api/login  (opsional untuk Sanctum)
    public function login(LoginRequest $req)
    {
        $u = User::where('username', strtolower(trim($req->username)))->first();
        if (!$u || !Hash::check($req->password, $u->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $token = $u->createToken('api')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user'  => $u->only(['id', 'username', 'role', 'id_fakultas', 'id_prodi']),
        ]);
    }
}

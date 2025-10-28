<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;

class UserController extends Controller
{
    // GET /api/users?role=&q=&fakultas_id=&prodi_id=&per_page=&nim=
    public function index()
    {
        $perPage = (int) request('per_page', 25);
        if ($perPage < 1)   $perPage = 1;
        if ($perPage > 200) $perPage = 200;

        $q = User::query()
            ->with([
                'fakultas:id,nama_fakultas',
                'prodi:id,id_fakultas,nama_prodi'
            ]);

        if ($role = request('role'))        $q->where('role', $role);
        if ($fid  = request('fakultas_id')) $q->where('id_fakultas', (int) $fid);
        if ($pid  = request('prodi_id'))    $q->where('id_prodi', (int) $pid);
        if ($nim  = request('nim'))         $q->where('nim', trim((string) $nim));

        if ($kw = trim((string) request('q', ''))) {
            $q->where(function ($w) use ($kw) {
                $w->where('username', 'like', "%{$kw}%")
                  ->orWhere('role', 'like', "%{$kw}%")
                  ->orWhere('nim', 'like', "%{$kw}%");
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

        // Normalisasi username lowercase
        if (isset($data['username'])) {
            $data['username'] = strtolower(trim($data['username']));
        }

        // Auto-derive untuk Mahasiswa
        if (($data['role'] ?? null) === 'Mahasiswa') {
            // username/password default = NIM jika tidak dikirim
            if (empty($data['username']) && !empty($data['nim'])) {
                $data['username'] = $data['nim'];
            }
            if (empty($data['password']) && !empty($data['nim'])) {
                $data['password'] = $data['nim'];
            }

            // derive id_prodi dari ref_mahasiswa jika belum ada
            if (empty($data['id_prodi']) && !empty($data['nim'])) {
                $data['id_prodi'] = DB::table('ref_mahasiswa')
                    ->where('nim', $data['nim'])
                    ->value('id_prodi');
            }
        }

        // Derive id_fakultas dari id_prodi jika tidak diisi
        if (empty($data['id_fakultas']) && !empty($data['id_prodi'])) {
            $data['id_fakultas'] = DB::table('ref_prodi')
                ->where('id', $data['id_prodi'])
                ->value('id_fakultas');
        }

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

        // Normalisasi username lowercase
        if (array_key_exists('username', $data) && $data['username'] !== null) {
            $data['username'] = strtolower(trim($data['username']));
        }

        // Jangan reset password jika kosong
        if (array_key_exists('password', $data) && (!$data['password'])) {
            unset($data['password']);
        }

        // Jika role Mahasiswa, pastikan turunan field terisi konsisten
        $roleFinal = $data['role'] ?? $user->role ?? null;

        if ($roleFinal === 'Mahasiswa') {
            // Jika username kosong namun nim ada, set username = nim
            if ((empty($data['username']) || !isset($data['username'])) && !empty($data['nim'])) {
                $data['username'] = $data['nim'];
            }
            // Jika tidak mengirim password dan user belum pernah set custom, boleh kosongkan agar tidak berubah
            // Jika mau paksa reset, lakukan di layer lain, default: tidak reset.

            // Derive id_prodi dari nim jika belum ada
            if (empty($data['id_prodi']) && !empty($data['nim'])) {
                $data['id_prodi'] = DB::table('ref_mahasiswa')
                    ->where('nim', $data['nim'])
                    ->value('id_prodi');
            }
        }

        // Derive id_fakultas dari id_prodi jika tidak diisi namun id_prodi ada
        if ((empty($data['id_fakultas']) || !isset($data['id_fakultas'])) && !empty($data['id_prodi'])) {
            $data['id_fakultas'] = DB::table('ref_prodi')
                ->where('id', $data['id_prodi'])
                ->value('id_fakultas');
        }

        $user->update($data);
        return response()->json($user->load('fakultas', 'prodi'));
    }

    // DELETE /api/users/{id}
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Cegah hapus diri sendiri
        if (Auth::check() && (int) Auth::id() === (int) $user->id) {
            return response()->json(['message' => 'Cannot delete self'], 422);
        }

        $user->delete();
        return response()->json(['deleted' => true]);
    }

    // POST /api/login
    public function login(LoginRequest $req)
    {
        $username = strtolower(trim($req->username));
        $u = User::where('username', $username)->first();

        if (!$u || !Hash::check($req->password, $u->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $u->createToken('api')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user'  => $u->only(['id', 'username', 'role', 'id_fakultas', 'id_prodi', 'nim']),
        ]);
    }
}

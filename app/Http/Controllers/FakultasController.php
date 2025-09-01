<?php

namespace App\Http\Controllers;

use App\Models\Fakultas;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class FakultasController extends BaseController
{
    public function index()
    {
        try {
            $data = Fakultas::all();
            return response()->json(['message' => 'Fakultas fetched successfully', 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch fakultas', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Storing fakultas:', $request->all());

            // username tetap unique (422). nama_fakultas kita cek manual untuk balas 409.
            $validated = $request->validate([
                'nama_fakultas'  => 'required|string|max:255',
                'username'       => 'required|string|max:100|unique:tb_fakultas,username',
                'password'       => 'required|string|min:8',
                'nama_dekan'     => 'required|string|max:255',
                'nip'            => 'required|string|max:100',
                'alamat'         => 'required|string|max:255',
            ]);

            // ❗ Cegah duplikasi: satu akun per fakultas (berdasarkan nama_fakultas)
            $exists = Fakultas::whereRaw('LOWER(nama_fakultas) = ?', [mb_strtolower($validated['nama_fakultas'])])->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'Akun Fakultas untuk nama tersebut sudah ada'
                ], 409);
            }

            $validated['password'] = bcrypt($validated['password']);
            $fakultas = Fakultas::create($validated);

            return response()->json(['message' => 'Fakultas created successfully', 'data' => $fakultas], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store fakultas', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $fakultas = Fakultas::findOrFail($id);
            return response()->json(['message' => 'Fakultas fetched successfully', 'data' => $fakultas], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Fakultas not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch fakultas', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $fakultas = Fakultas::findOrFail($id);
            Log::info('Updating fakultas:', $request->all());

            $validated = $request->validate([
                'nama_fakultas'  => 'sometimes|string|max:255',
                'username'       => 'sometimes|string|max:100|unique:tb_fakultas,username,' . $id . ',id_fakultas',
                'password'       => 'sometimes|string|min:8',
                'nama_dekan'     => 'sometimes|string|max:255',
                'nip'            => 'sometimes|string|max:100',
                'alamat'         => 'sometimes|string|max:255',
            ]);

            // ❗ Jika nama_fakultas diubah, pastikan tidak menabrak fakultas lain (409)
            if (array_key_exists('nama_fakultas', $validated)) {
                $conflict = Fakultas::whereRaw('LOWER(nama_fakultas) = ?', [mb_strtolower($validated['nama_fakultas'])])
                    ->where('id_fakultas', '!=', $id)
                    ->exists();
                if ($conflict) {
                    return response()->json([
                        'message' => 'Akun Fakultas untuk nama tersebut sudah ada'
                    ], 409);
                }
            }

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $fakultas->update($validated);

            return response()->json(['message' => 'Fakultas updated successfully', 'data' => $fakultas], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Fakultas not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update fakultas', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $fakultas = Fakultas::findOrFail($id);
            $fakultas->delete();
            return response()->json(['message' => 'Fakultas deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Fakultas not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete fakultas', 'error' => $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class MahasiswaController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = Mahasiswa::with('prodi');

            // Filter by id_prodi
            if ($request->has('id_prodi')) {
                $query->where('id_prodi', $request->id_prodi);
            }

            // Filter by id_fakultas via prodi
            if ($request->has('id_fakultas')) {
                $query->whereHas('prodi.fakultas', function ($q) use ($request) {
                    $q->where('id_fakultas', $request->id_fakultas);
                });
            }

            $data = $query->get();

            return response()->json([
                'message' => 'Mahasiswa fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch mahasiswa',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function store(Request $request)
    {
        try {
            Log::info('Storing mahasiswa:', $request->all());
            $validated = $request->validate([
                'id_prodi' => 'required|integer|exists:tb_prodi,id_prodi',
                'nama_mahasiswa' => 'required|string',
                'username' => 'required|string|unique:tb_mahasiswa,username',
                'password' => 'required|string',
                'tgl_masuk' => 'required|date',
                'tgl_keluar' => 'nullable|date',
                'no_telp' => 'nullable|string',
                'alamat' => 'nullable|string',
                'tanggal_lahir' => 'required|date',
                'tempat_lahir' => 'required|string',
                'nim_mahasiswa' => 'required|string|unique:tb_mahasiswa,nim_mahasiswa',
            ]);

            $validated['password'] = bcrypt($validated['password']);
            $mahasiswa = Mahasiswa::create($validated);

            return response()->json(['message' => 'Mahasiswa created successfully', 'data' => $mahasiswa], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store mahasiswa', 'error' => $e->getMessage()], 500);
        }
    }


    public function show($id)
    {
        try {
            $mahasiswa = Mahasiswa::findOrFail($id);
            return response()->json(['message' => 'Mahasiswa fetched successfully', 'data' => $mahasiswa], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Mahasiswa not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch mahasiswa', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $mahasiswa = Mahasiswa::findOrFail($id);
            Log::info('Updating mahasiswa:', $request->all());

            $validated = $request->validate([
                'id_prodi' => 'sometimes|integer|exists:tb_prodi,id_prodi',
                'nama_mahasiswa' => 'sometimes|string',
                'username' => 'sometimes|string|unique:tb_mahasiswa,username,' . $id . ',id_mahasiswa',
                'password' => 'sometimes|string',
                'tgl_keluar' => 'sometimes|date|nullable',
                'tgl_masuk' => 'sometimes|date',
                'no_telp' => 'sometimes|string',
                'alamat' => 'sometimes|string',
                'tanggal_lahir' => 'sometimes|date',
                'tempat_lahir' => 'sometimes|string',
                'nim_mahasiswa' => 'sometimes|string|unique:tb_mahasiswa,nim_mahasiswa,' . $id . ',id_mahasiswa',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $mahasiswa->update($validated);

            return response()->json(['message' => 'Mahasiswa updated successfully', 'data' => $mahasiswa], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Mahasiswa not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update mahasiswa', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $mahasiswa = Mahasiswa::findOrFail($id);
            $mahasiswa->delete();
            return response()->json(['message' => 'Mahasiswa deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Mahasiswa not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete mahasiswa', 'error' => $e->getMessage()], 500);
        }
    }
}

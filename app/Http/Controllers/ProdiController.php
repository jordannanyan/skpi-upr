<?php

namespace App\Http\Controllers;

use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class ProdiController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = Prodi::with('fakultas');

            // Optional filter by id_fakultas
            if ($request->has('id_fakultas')) {
                $query->where('id_fakultas', $request->id_fakultas);
            }

            $data = $query->get();

            return response()->json([
                'message' => 'Prodi fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch prodi',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            Log::info('Storing prodi:', $request->all());
            $validated = $request->validate([
                'id_fakultas' => 'required|exists:tb_fakultas,id_fakultas',
                'nama_prodi' => 'required|string',
                'username' => 'required|string|unique:tb_prodi,username',
                'password' => 'required|string',
                'akreditasi' => 'nullable|string',
                'sk_akre' => 'nullable|string',
                'jenis_jenjang' => 'nullable|string',
                'kompetensi_kerja' => 'nullable|string',
                'bahasa' => 'nullable|string',
                'penilaian' => 'nullable|string',
                'jenis_lanjutan' => 'nullable|string',
                'alamat' => 'nullable|string'
            ]);

            $validated['password'] = bcrypt($validated['password']);
            $prodi = Prodi::create($validated);

            return response()->json(['message' => 'Prodi created successfully', 'data' => $prodi], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store prodi', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $prodi = Prodi::findOrFail($id);
            return response()->json(['message' => 'Prodi fetched successfully', 'data' => $prodi], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Prodi not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch prodi', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $prodi = Prodi::findOrFail($id);
            Log::info('Updating prodi:', $request->all());

            $validated = $request->validate([
                'id_fakultas' => 'sometimes|exists:tb_fakultas,id_fakultas',
                'nama_prodi' => 'sometimes|string',
                'username' => 'sometimes|string|unique:tb_prodi,username,' . $id . ',id_prodi',
                'password' => 'sometimes|string',
                'akreditasi' => 'nullable|string',
                'sk_akre' => 'nullable|string',
                'jenis_jenjang' => 'nullable|string',
                'kompetensi_kerja' => 'nullable|string',
                'bahasa' => 'nullable|string',
                'penilaian' => 'nullable|string',
                'jenis_lanjutan' => 'nullable|string',
                'alamat' => 'nullable|string'
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $prodi->update($validated);

            return response()->json(['message' => 'Prodi updated successfully', 'data' => $prodi], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Prodi not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update prodi', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $prodi = Prodi::findOrFail($id);
            $prodi->delete();
            return response()->json(['message' => 'Prodi deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Prodi not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete prodi', 'error' => $e->getMessage()], 500);
        }
    }
}

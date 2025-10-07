<?php

namespace App\Http\Controllers;

use App\Models\Sertifikasi;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller as BaseController;

class SertifikasiController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = Sertifikasi::with('mahasiswa.prodi.fakultas');

            // Optional filter by id_mahasiswa
            if ($request->has('id_mahasiswa')) {
                $query->where('id_mahasiswa', $request->id_mahasiswa);
            }

            // Optional filter by id_prodi
            if ($request->has('id_prodi')) {
                $query->whereHas('mahasiswa.prodi', function ($q) use ($request) {
                    $q->where('id_prodi', $request->id_prodi);
                });
            }

            // Optional filter by id_fakultas
            if ($request->has('id_fakultas')) {
                $query->whereHas('mahasiswa.prodi.fakultas', function ($q) use ($request) {
                    $q->where('id_fakultas', $request->id_fakultas);
                });
            }

            if ($request->has('q')) {
                // $query->whereHas('mahasiswa', function ($q) use ($request) {
                //     $q->where('nama_mahasiswa', 'like', '%' . $request->q . '%');
                // });
                // Group the search conditions to ensure proper OR logic
                $query->where(function ($subQuery) use ($request) {
                    // Search by 'nama_kegiatan' in the current table
                    $subQuery->where('nama_sertifikasi', 'like', '%' . $request->q . '%')
                            // OR search by 'nama_mahasiswa' in the related 'mahasiswa' table
                            ->orWhereHas('mahasiswa', function ($relationQuery) use ($request) {
                                $relationQuery->where('nama_mahasiswa', 'like', '%' . $request->q . '%');
                            });
                });
            }

            $data = $query->get();

            return response()->json([
                'message' => 'Sertifikasi fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch sertifikasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            Log::info('Storing sertifikasi:', $request->all());
            $validated = $request->validate([
                'id_mahasiswa' => 'required|exists:tb_mahasiswa,id_mahasiswa',
                'nama_sertifikasi' => 'required|string',
                'kategori_sertifikasi' => 'required|string',
                'file_sertifikat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
            ]);

            if ($request->hasFile('file_sertifikat')) {
                $validated['file_sertifikat'] = $request->file('file_sertifikat')->store('sertifikasi', 'public');
            }

            $sertif = Sertifikasi::create($validated);
            return response()->json(['message' => 'Sertifikasi created successfully', 'data' => $sertif], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store sertifikasi', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $sertif = Sertifikasi::with('mahasiswa')->findOrFail($id);
            return response()->json(['message' => 'Sertifikasi fetched successfully', 'data' => $sertif], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sertifikasi not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch sertifikasi', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $sertif = Sertifikasi::findOrFail($id);
            Log::info('Updating sertifikasi:', $request->all());

            $validated = $request->validate([
                'id_mahasiswa' => 'sometimes|exists:tb_mahasiswa,id_mahasiswa',
                'nama_sertifikasi' => 'sometimes|string',
                'kategori_sertifikasi' => 'sometimes|string',
                'file_sertifikat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
            ]);

            if ($request->hasFile('file_sertifikat')) {
                $validated['file_sertifikat'] = $request->file('file_sertifikat')->store('sertifikasi', 'public');
            }

            $sertif->update($validated);
            return response()->json(['message' => 'Sertifikasi updated successfully', 'data' => $sertif], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sertifikasi not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update sertifikasi', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $sertif = Sertifikasi::findOrFail($id);
            $sertif->delete();
            return response()->json(['message' => 'Sertifikasi deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Sertifikasi not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete sertifikasi', 'error' => $e->getMessage()], 500);
        }
    }
}

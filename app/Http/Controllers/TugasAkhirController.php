<?php

namespace App\Http\Controllers;

use App\Models\TugasAkhir;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller as BaseController;

class TugasAkhirController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = TugasAkhir::with('mahasiswa.prodi.fakultas');

            // Filter by id_mahasiswa
            if ($request->has('id_mahasiswa')) {
                $query->where('id_mahasiswa', $request->id_mahasiswa);
            }

            // Filter by id_prodi
            if ($request->has('id_prodi')) {
                $query->whereHas('mahasiswa.prodi', function ($q) use ($request) {
                    $q->where('id_prodi', $request->id_prodi);
                });
            }

            // Filter by id_fakultas
            if ($request->has('id_fakultas')) {
                $query->whereHas('mahasiswa.prodi.fakultas', function ($q) use ($request) {
                    $q->where('id_fakultas', $request->id_fakultas);
                });
            }

            $data = $query->get();

            return response()->json([
                'message' => 'Tugas Akhir fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch tugas akhir',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            Log::info('Storing tugas akhir:', $request->all());
            $validated = $request->validate([
                'id_mahasiswa' => 'required|exists:tb_mahasiswa,id_mahasiswa',
                'kategori' => 'required|string',
                'judul' => 'required|string',
                'file_halaman_dpn' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'file_lembar_pengesahan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
            ]);

            if ($request->hasFile('file_halaman_dpn')) {
                $filePath = $request->file('file_halaman_dpn')->store('tugas_akhir', 'public');
                $validated['file_halaman_dpn'] = $filePath;
            }

            if ($request->hasFile('file_lembar_pengesahan')) {
                $filePath = $request->file('file_lembar_pengesahan')->store('tugas_akhir', 'public');
                $validated['file_lembar_pengesahan'] = $filePath;
            }

            $ta = TugasAkhir::create($validated);

            return response()->json(['message' => 'Tugas Akhir created successfully', 'data' => $ta], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store tugas akhir', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $ta = TugasAkhir::with('mahasiswa')->findOrFail($id);
            return response()->json(['message' => 'Tugas Akhir fetched successfully', 'data' => $ta], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Tugas Akhir not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch tugas akhir', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $ta = TugasAkhir::findOrFail($id);
            Log::info('Updating tugas akhir:', $request->all());

            $validated = $request->validate([
                'id_mahasiswa' => 'sometimes|exists:tb_mahasiswa,id_mahasiswa',
                'kategori' => 'sometimes|string',
                'judul' => 'sometimes|string',
                'file_halaman_dpn' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'file_lembar_pengesahan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
            ]);

            if ($request->hasFile('file_halaman_dpn')) {
                $filePath = $request->file('file_halaman_dpn')->store('tugas_akhir', 'public');
                $validated['file_halaman_dpn'] = $filePath;
            }

            if ($request->hasFile('file_lembar_pengesahan')) {
                $filePath = $request->file('file_lembar_pengesahan')->store('tugas_akhir', 'public');
                $validated['file_lembar_pengesahan'] = $filePath;
            }

            $ta->update($validated);

            return response()->json(['message' => 'Tugas Akhir updated successfully', 'data' => $ta], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Tugas Akhir not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update tugas akhir', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $ta = TugasAkhir::findOrFail($id);
            $ta->delete();
            return response()->json(['message' => 'Tugas Akhir deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Tugas Akhir not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete tugas akhir', 'error' => $e->getMessage()], 500);
        }
    }
}

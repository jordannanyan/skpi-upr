<?php

namespace App\Http\Controllers;

use App\Models\KerjaPraktek;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller as BaseController;

class KerjaPraktekController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = KerjaPraktek::with('mahasiswa');

            // Filter by id_mahasiswa (direct)
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

            $data = $query->get();

            return response()->json([
                'message' => 'Kerja Praktek fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch kerja praktek',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            Log::info('Storing kerja praktek:', $request->all());
            $validated = $request->validate([
                'id_mahasiswa' => 'required|exists:tb_mahasiswa,id_mahasiswa',
                'nama_kegiatan' => 'required|string',
                'file_sertifikat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
            ]);

            if ($request->hasFile('file_sertifikat')) {
                $filePath = $request->file('file_sertifikat')->store('kerja_praktek', 'public');
                $validated['file_sertifikat'] = $filePath;
            }

            $kp = KerjaPraktek::create($validated);

            return response()->json(['message' => 'Kerja Praktek created successfully', 'data' => $kp], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store kerja praktek', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $kp = KerjaPraktek::with('mahasiswa')->findOrFail($id);
            return response()->json(['message' => 'Kerja Praktek fetched successfully', 'data' => $kp], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Kerja Praktek not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch kerja praktek', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $kp = KerjaPraktek::findOrFail($id);
            Log::info('Updating kerja praktek:', $request->all());

            $validated = $request->validate([
                'id_mahasiswa' => 'sometimes|exists:tb_mahasiswa,id_mahasiswa',
                'nama_kegiatan' => 'sometimes|string',
                'file_sertifikat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
            ]);

            if ($request->hasFile('file_sertifikat')) {
                $filePath = $request->file('file_sertifikat')->store('kerja_praktek', 'public');
                $validated['file_sertifikat'] = $filePath;
            }

            $kp->update($validated);

            return response()->json(['message' => 'Kerja Praktek updated successfully', 'data' => $kp], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Kerja Praktek not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update kerja praktek', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $kp = KerjaPraktek::findOrFail($id);
            $kp->delete();
            return response()->json(['message' => 'Kerja Praktek deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Kerja Praktek not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete kerja praktek', 'error' => $e->getMessage()], 500);
        }
    }
}

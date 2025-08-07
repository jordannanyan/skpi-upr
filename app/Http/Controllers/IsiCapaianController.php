<?php

namespace App\Http\Controllers;

use App\Models\IsiCapaian;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class IsiCapaianController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = IsiCapaian::with('cplSkor.mahasiswa');

            // Optional filter by id_prodi
            if ($request->has('id_prodi')) {
                $query->whereHas('cplSkor.mahasiswa.prodi', function ($q) use ($request) {
                    $q->where('id_prodi', $request->id_prodi);
                });
            }

            // Optional filter by id_fakultas
            if ($request->has('id_fakultas')) {
                $query->whereHas('cplSkor.mahasiswa.prodi.fakultas', function ($q) use ($request) {
                    $q->where('id_fakultas', $request->id_fakultas);
                });
            }

            $data = $query->get();

            return response()->json([
                'message' => 'Isi capaian fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch isi capaian',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            Log::info('Storing isi capaian:', $request->all());
            $validated = $request->validate([
                'deskripsi_indo' => 'required|string',
                'id_cpl_skor' => 'required|exists:tb_cpl_skor,id_cpl_skor',
                'deskripsi_inggris' => 'required|string'
            ]);

            $isiCapaian = IsiCapaian::create($validated);

            return response()->json(['message' => 'Isi capaian created successfully', 'data' => $isiCapaian], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store isi capaian', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $isiCapaian = IsiCapaian::with('cplSkor')->findOrFail($id);
            return response()->json(['message' => 'Isi capaian fetched successfully', 'data' => $isiCapaian], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Isi capaian not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch isi capaian', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $isiCapaian = IsiCapaian::findOrFail($id);
            Log::info('Updating isi capaian:', $request->all());

            $validated = $request->validate([
                'deskripsi_indo' => 'sometimes|string',
                'id_cpl_skor' => 'sometimes|exists:tb_cpl_skor,id_cpl_skor',
                'deskripsi_inggris' => 'sometimes|string'
            ]);

            $isiCapaian->update($validated);

            return response()->json(['message' => 'Isi capaian updated successfully', 'data' => $isiCapaian], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Isi capaian not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update isi capaian', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $isiCapaian = IsiCapaian::findOrFail($id);
            $isiCapaian->delete();
            return response()->json(['message' => 'Isi capaian deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Isi capaian not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete isi capaian', 'error' => $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CplSkor;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class CplSkorController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = CplSkor::with(['cpl', 'mahasiswa']);

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
                'message' => 'CPL Skor fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch CPL Skor',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            Log::info('Storing CPL Skor:', $request->all());
            $validated = $request->validate([
                'id_cpl' => 'required|exists:tb_cpl,id_cpl',
                'id_mahasiswa' => 'required|exists:tb_mahasiswa,id_mahasiswa',
                'skor_cpl' => 'required|numeric'
            ]);

            $skor = CplSkor::create($validated);

            return response()->json(['message' => 'CPL Skor created successfully', 'data' => $skor], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store CPL Skor', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $skor = CplSkor::with(['cpl', 'mahasiswa'])->findOrFail($id);
            return response()->json(['message' => 'CPL Skor fetched successfully', 'data' => $skor], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'CPL Skor not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch CPL Skor', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $skor = CplSkor::findOrFail($id);
            Log::info('Updating CPL Skor:', $request->all());

            $validated = $request->validate([
                'id_cpl' => 'sometimes|exists:tb_cpl,id_cpl',
                'id_mahasiswa' => 'sometimes|exists:tb_mahasiswa,id_mahasiswa',
                'skor_cpl' => 'sometimes|numeric'
            ]);

            $skor->update($validated);

            return response()->json(['message' => 'CPL Skor updated successfully', 'data' => $skor], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'CPL Skor not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update CPL Skor', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $skor = CplSkor::findOrFail($id);
            $skor->delete();
            return response()->json(['message' => 'CPL Skor deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'CPL Skor not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete CPL Skor', 'error' => $e->getMessage()], 500);
        }
    }
}

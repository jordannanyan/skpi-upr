<?php

namespace App\Http\Controllers;

use App\Models\Cetak;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class CetakController extends BaseController
{
    public function index()
    {
        try {
            $data = Cetak::with(['cplSkor', 'pengesahan'])->get();
            return response()->json(['message' => 'Cetak fetched successfully', 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch cetak', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Storing cetak:', $request->all());
            $validated = $request->validate([
                'id_cpl_skor' => 'required|exists:tb_cpl_skor,id_cpl_skor',
                'id_pengesahan' => 'required|exists:tb_pengesahan,id_pengesahan',
                'status' => 'required|in:aktif,noaktif',
                'tgl_cetak' => 'required|date'
            ]);

            $cetak = Cetak::create($validated);

            return response()->json(['message' => 'Cetak created successfully', 'data' => $cetak], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store cetak', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $cetak = Cetak::with(['cplSkor', 'pengesahan'])->findOrFail($id);
            return response()->json(['message' => 'Cetak fetched successfully', 'data' => $cetak], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Cetak not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch cetak', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $cetak = Cetak::findOrFail($id);
            Log::info('Updating cetak:', $request->all());

            $validated = $request->validate([
                'id_cpl_skor' => 'sometimes|exists:tb_cpl_skor,id_cpl_skor',
                'id_pengesahan' => 'sometimes|exists:tb_pengesahan,id_pengesahan',
                'status' => 'sometimes|in:aktif,noaktif',
                'tgl_cetak' => 'sometimes|date'
            ]);

            $cetak->update($validated);

            return response()->json(['message' => 'Cetak updated successfully', 'data' => $cetak], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Cetak not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update cetak', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $cetak = Cetak::findOrFail($id);
            $cetak->delete();
            return response()->json(['message' => 'Cetak deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Cetak not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete cetak', 'error' => $e->getMessage()], 500);
        }
    }
}

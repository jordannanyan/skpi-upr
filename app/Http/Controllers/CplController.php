<?php

namespace App\Http\Controllers;

use App\Models\Cpl;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class CplController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = Cpl::query();

            // Filter by id_prodi
            if ($request->has('id_prodi')) {
                $query->whereHas('cplSkors.mahasiswa.prodi', function ($q) use ($request) {
                    $q->where('id_prodi', $request->id_prodi);
                });
            }

            // Filter by id_fakultas
            if ($request->has('id_fakultas')) {
                $query->whereHas('cplSkors.mahasiswa.prodi.fakultas', function ($q) use ($request) {
                    $q->where('id_fakultas', $request->id_fakultas);
                });
            }

            $data = $query->get();

            return response()->json([
                'message' => 'CPL fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch CPL',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Storing CPL:', $request->all());
            $validated = $request->validate([
                'nama_cpl' => 'required|string',
                'status' => 'required|in:aktif,noaktif'
            ]);

            $cpl = Cpl::create($validated);

            return response()->json(['message' => 'CPL created successfully', 'data' => $cpl], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store CPL', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $cpl = Cpl::findOrFail($id);
            return response()->json(['message' => 'CPL fetched successfully', 'data' => $cpl], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'CPL not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch CPL', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $cpl = Cpl::findOrFail($id);
            Log::info('Updating CPL:', $request->all());

            $validated = $request->validate([
                'nama_cpl' => 'sometimes|string',
                'status' => 'sometimes|in:aktif,noaktif'
            ]);

            $cpl->update($validated);

            return response()->json(['message' => 'CPL updated successfully', 'data' => $cpl], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'CPL not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update CPL', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $cpl = Cpl::findOrFail($id);
            $cpl->delete();
            return response()->json(['message' => 'CPL deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'CPL not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete CPL', 'error' => $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CplMaster;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class CplMasterController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = CplMaster::with(['prodi.fakultas', 'kategori']);

            if ($request->filled('id_prodi')) {
                $query->where('id_prodi', $request->id_prodi);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $data = $query->orderBy('id_prodi')->orderBy('kode')->get();

            return response()->json(['message' => 'CPL master fetched successfully', 'data' => $data], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to fetch CPL master', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Storing CPL master:', $request->all());

            $validated = $request->validate([
                'id_prodi'  => 'required|exists:tb_prodi,id_prodi',
                'id_cpl'    => 'nullable|exists:tb_cpl,id_cpl', // kategori opsional
                'kode'      => 'required|string|max:20',        // CPL1, CPL2, ...
                'nama_cpl'  => 'nullable|string|max:255',
                'deskripsi' => 'nullable|string',
                'status'    => 'nullable|in:aktif,noaktif',
            ]);

            // ❗ 1 prodi tidak boleh punya kode CPL yang sama
            $exists = CplMaster::where('id_prodi', $validated['id_prodi'])
                ->whereRaw('LOWER(kode) = ?', [mb_strtolower($validated['kode'])])
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Kode CPL sudah ada pada prodi tersebut'], 409);
            }

            $validated['status'] = $validated['status'] ?? 'aktif';

            $row = CplMaster::create($validated);

            return response()->json(['message' => 'CPL master created successfully', 'data' => $row], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to store CPL master', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $row = CplMaster::with(['prodi.fakultas', 'kategori'])->findOrFail($id);
            return response()->json(['message' => 'CPL master fetched successfully', 'data' => $row], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'CPL master not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to fetch CPL master', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $row = CplMaster::findOrFail($id);
            Log::info('Updating CPL master:', $request->all());

            $validated = $request->validate([
                'id_prodi'  => 'sometimes|exists:tb_prodi,id_prodi',
                'id_cpl'    => 'sometimes|nullable|exists:tb_cpl,id_cpl',
                'kode'      => 'sometimes|string|max:20',
                'nama_cpl'  => 'sometimes|nullable|string|max:255',
                'deskripsi' => 'sometimes|nullable|string',
                'status'    => 'sometimes|in:aktif,noaktif',
            ]);

            // ❗ Cek konflik (id_prodi, kode)
            if (array_key_exists('id_prodi', $validated) || array_key_exists('kode', $validated)) {
                $targetProdi = $validated['id_prodi'] ?? $row->id_prodi;
                $targetKode  = $validated['kode']     ?? $row->kode;

                $conflict = CplMaster::where('id_prodi', $targetProdi)
                    ->whereRaw('LOWER(kode) = ?', [mb_strtolower($targetKode)])
                    ->where('id_cpl_master', '!=', $id)
                    ->exists();

                if ($conflict) {
                    return response()->json(['message' => 'Kode CPL sudah ada pada prodi tersebut'], 409);
                }
            }

            $row->update($validated);

            return response()->json(['message' => 'CPL master updated successfully', 'data' => $row], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'CPL master not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to update CPL master', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $row = CplMaster::findOrFail($id);
            $row->delete();
            return response()->json(['message' => 'CPL master deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'CPL master not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to delete CPL master', 'error' => $e->getMessage()], 500);
        }
    }
}

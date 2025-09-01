<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class PengajuanController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = Pengajuan::with(['mahasiswa.prodi.fakultas', 'kategori']);

            if ($request->has('id_mahasiswa')) {
                $query->where('id_mahasiswa', $request->id_mahasiswa);
            }

            if ($request->has('id_prodi')) {
                $query->whereHas('mahasiswa.prodi', function ($q) use ($request) {
                    $q->where('id_prodi', $request->id_prodi);
                });
            }

            if ($request->has('id_fakultas')) {
                $query->whereHas('mahasiswa.prodi.fakultas', function ($q) use ($request) {
                    $q->where('id_fakultas', $request->id_fakultas);
                });
            }

            $data = $query->get();

            return response()->json([
                'message' => 'Pengajuan fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch pengajuan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Storing pengajuan:', $request->all());

            $validated = $request->validate([
                'id_mahasiswa'   => 'required|exists:tb_mahasiswa,id_mahasiswa',
                'id_kategori'    => 'required|exists:tb_kategori,id_kategori',
                'status'         => 'required|in:aktif,noaktif',
                'tgl_pengajuan'  => 'required|date',
            ]);

            // ❗ Cegah duplikasi pengajuan per mahasiswa
            $already = Pengajuan::where('id_mahasiswa', $validated['id_mahasiswa'])->exists();
            if ($already) {
                return response()->json([
                    'message' => 'Pengajuan sudah ada untuk mahasiswa ini'
                ], 409);
            }

            $pengajuan = Pengajuan::create($validated);

            return response()->json([
                'message' => 'Pengajuan created successfully',
                'data' => $pengajuan
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store pengajuan', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $pengajuan = Pengajuan::with(['mahasiswa', 'kategori'])->findOrFail($id);
            return response()->json(['message' => 'Pengajuan fetched successfully', 'data' => $pengajuan], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pengajuan not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch pengajuan', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $pengajuan = Pengajuan::findOrFail($id);
            Log::info('Updating pengajuan:', $request->all());

            $validated = $request->validate([
                'id_mahasiswa'   => 'sometimes|exists:tb_mahasiswa,id_mahasiswa',
                'id_kategori'    => 'sometimes|exists:tb_kategori,id_kategori',
                'status'         => 'sometimes|in:aktif,noaktif',
                'tgl_pengajuan'  => 'sometimes|date'
            ]);

            // ❗ Jaga agar tetap satu pengajuan per mahasiswa saat update
            if (array_key_exists('id_mahasiswa', $validated)) {
                $pkName = $pengajuan->getKeyName();   // menangani pk kustom
                $pkVal  = $pengajuan->getKey();

                $conflict = Pengajuan::where('id_mahasiswa', $validated['id_mahasiswa'])
                    ->where($pkName, '!=', $pkVal)
                    ->exists();

                if ($conflict) {
                    return response()->json([
                        'message' => 'Mahasiswa tersebut sudah memiliki pengajuan'
                    ], 409);
                }
            }

            $pengajuan->update($validated);

            return response()->json(['message' => 'Pengajuan updated successfully', 'data' => $pengajuan], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pengajuan not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update pengajuan', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $pengajuan = Pengajuan::findOrFail($id);
            $pengajuan->delete();
            return response()->json(['message' => 'Pengajuan deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pengajuan not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete pengajuan', 'error' => $e->getMessage()], 500);
        }
    }
}

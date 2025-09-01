<?php

namespace App\Http\Controllers;

use App\Models\CplSkor;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class CplSkorController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = CplSkor::with([
                'cplMaster.kategori',          // kategori CPL (opsional)
                'cplMaster.prodi.fakultas',
                'mahasiswa.prodi.fakultas',
            ]);

            // Filter opsional
            if ($request->filled('id_mahasiswa')) {
                $query->where('id_mahasiswa', $request->id_mahasiswa);
            }
            if ($request->filled('id_cpl_master')) {
                $query->where('id_cpl_master', $request->id_cpl_master);
            }
            if ($request->filled('id_prodi')) {
                $query->whereHas('mahasiswa.prodi', function ($q) use ($request) {
                    $q->where('id_prodi', $request->id_prodi);
                });
            }
            if ($request->filled('id_fakultas')) {
                $query->whereHas('mahasiswa.prodi.fakultas', function ($q) use ($request) {
                    $q->where('id_fakultas', $request->id_fakultas);
                });
            }

            $data = $query->get();

            return response()->json([
                'message' => 'CPL Skor fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to fetch CPL Skor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Storing CPL Skor:', $request->all());

            $validated = $request->validate([
                'id_cpl_master' => 'required|exists:tb_cpl_master,id_cpl_master',
                'id_mahasiswa'  => 'required|exists:tb_mahasiswa,id_mahasiswa',
                'skor_cpl'      => 'required|numeric',
            ]);

            // ❗ Cegah duplikasi: 1 skor per (mahasiswa x CPL master)
            $exists = CplSkor::where('id_mahasiswa',  $validated['id_mahasiswa'])
                ->where('id_cpl_master', $validated['id_cpl_master'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Skor untuk mahasiswa dan CPL tersebut sudah ada'
                ], 409);
            }

            $skor = CplSkor::create([
                'id_cpl_master' => $validated['id_cpl_master'],
                'id_mahasiswa'  => $validated['id_mahasiswa'],
                'skor_cpl'      => $validated['skor_cpl'],
            ]);

            return response()->json([
                'message' => 'CPL Skor created successfully',
                'data'    => $skor
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to store CPL Skor', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $skor = CplSkor::with(['cplMaster.kategori','cplMaster.prodi','mahasiswa'])->findOrFail($id);
            return response()->json(['message' => 'CPL Skor fetched successfully', 'data' => $skor], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'CPL Skor not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to fetch CPL Skor', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $skor = CplSkor::findOrFail($id);
            Log::info('Updating CPL Skor:', $request->all());

            $validated = $request->validate([
                'id_cpl_master' => 'sometimes|exists:tb_cpl_master,id_cpl_master',
                'id_mahasiswa'  => 'sometimes|exists:tb_mahasiswa,id_mahasiswa',
                'skor_cpl'      => 'sometimes|numeric',
            ]);

            // ❗ Cek konflik duplikasi saat ganti mahasiswa/CPL master
            if (array_key_exists('id_cpl_master', $validated) || array_key_exists('id_mahasiswa', $validated)) {
                $targetCpl = $validated['id_cpl_master'] ?? $skor->id_cpl_master;
                $targetMhs = $validated['id_mahasiswa']  ?? $skor->id_mahasiswa;

                $conflict = CplSkor::where('id_cpl_master', $targetCpl)
                    ->where('id_mahasiswa', $targetMhs)
                    ->where('id_cpl_skor', '!=', $id)
                    ->exists();

                if ($conflict) {
                    return response()->json([
                        'message' => 'Skor untuk mahasiswa dan CPL tersebut sudah ada'
                    ], 409);
                }
            }

            $skor->update($validated);

            return response()->json(['message' => 'CPL Skor updated successfully', 'data' => $skor], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'CPL Skor not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to delete CPL Skor', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * BULK UPSERT: Simpan banyak skor untuk satu mahasiswa sekaligus.
     * Payload:
     * {
     *   "id_mahasiswa": 123,
     *   "items": [
     *     {"id_cpl_master": 10, "skor_cpl": 82.5},
     *     {"id_cpl_master": 11, "skor_cpl": 90}
     *   ]
     * }
     */
    public function bulkUpsert(Request $request)
    {
        try {
            Log::info('Bulk upsert CPL Skor:', $request->all());

            $validated = $request->validate([
                'id_mahasiswa'                 => 'required|exists:tb_mahasiswa,id_mahasiswa',
                'items'                        => 'required|array|min:1',
                'items.*.id_cpl_master'        => 'required|exists:tb_cpl_master,id_cpl_master',
                'items.*.skor_cpl'             => 'required|numeric',
            ]);

            $idMhs = (int) $validated['id_mahasiswa'];
            $items = $validated['items'];

            // Normalisasi: jika ada duplikasi id_cpl_master dalam payload, pakai nilai terakhir
            $collapsed = [];
            foreach ($items as $it) {
                $collapsed[(int)$it['id_cpl_master']] = (float)$it['skor_cpl'];
            }

            DB::beginTransaction();

            $created = 0;
            $updated = 0;
            $results = [];

            foreach ($collapsed as $idCplMaster => $nilai) {
                $row = CplSkor::updateOrCreate(
                    ['id_mahasiswa' => $idMhs, 'id_cpl_master' => $idCplMaster],
                    ['skor_cpl' => $nilai]
                );

                if ($row->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                $results[] = $row;
            }

            DB::commit();

            return response()->json([
                'message' => "Berhasil menyimpan skor. created={$created}, updated={$updated}",
                'created' => $created,
                'updated' => $updated,
                'data'    => $results,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (QueryException $qe) {
            DB::rollBack();
            return response()->json(['message' => 'DB error', 'error' => $qe->getMessage()], 500);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to bulk upsert', 'error' => $e->getMessage()], 500);
        }
    }
}

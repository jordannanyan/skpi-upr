<?php

namespace App\Http\Controllers;

use App\Models\Pengesahan;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;

class PengesahanController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = Pengesahan::with([
                'fakultas',
                'pengajuan.mahasiswa.prodi',
                'pengajuan.kategori'
            ]);

            // Filter by id_mahasiswa
            if ($request->has('id_mahasiswa')) {
                $query->whereHas('pengajuan.mahasiswa', function ($q) use ($request) {
                    $q->where('id_mahasiswa', $request->id_mahasiswa);
                });
            }

            // Filter by id_prodi
            if ($request->has('id_prodi')) {
                $query->whereHas('pengajuan.mahasiswa.prodi', function ($q) use ($request) {
                    $q->where('id_prodi', $request->id_prodi);
                });
            }

            // Filter by id_fakultas
            if ($request->has('id_fakultas')) {
                $query->whereHas('fakultas', function ($q) use ($request) {
                    $q->where('id_fakultas', $request->id_fakultas);
                });
            }

            $data = $query->get();

            return response()->json([
                'message' => 'Pengesahan fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch pengesahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            Log::info('Storing pengesahan:', $request->all());
            $validated = $request->validate([
                'id_fakultas' => 'required|exists:tb_fakultas,id_fakultas',
                'id_pengajuan' => 'required|exists:tb_pengajuan,id_pengajuan',
                'tgl_pengesahan' => 'required|date',
                'nomor_pengesahan' => 'required|string'
            ]);

            $pengesahan = Pengesahan::create($validated);

            return response()->json(['message' => 'Pengesahan created successfully', 'data' => $pengesahan], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store pengesahan', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $pengesahan = Pengesahan::with(['fakultas', 'pengajuan'])->findOrFail($id);
            return response()->json(['message' => 'Pengesahan fetched successfully', 'data' => $pengesahan], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pengesahan not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch pengesahan', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $pengesahan = Pengesahan::findOrFail($id);
            Log::info('Updating pengesahan:', $request->all());

            $validated = $request->validate([
                'id_fakultas' => 'sometimes|exists:tb_fakultas,id_fakultas',
                'id_pengajuan' => 'sometimes|exists:tb_pengajuan,id_pengajuan',
                'tgl_pengesahan' => 'sometimes|date',
                'nomor_pengesahan' => 'sometimes|string'
            ]);

            $pengesahan->update($validated);

            return response()->json(['message' => 'Pengesahan updated successfully', 'data' => $pengesahan], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pengesahan not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update pengesahan', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $pengesahan = Pengesahan::findOrFail($id);
            $pengesahan->delete();
            return response()->json(['message' => 'Pengesahan deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pengesahan not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete pengesahan', 'error' => $e->getMessage()], 500);
        }
    }

    public function getPengesahanDetail($id)
    {
        try {
            $pengesahan = Pengesahan::with([
                'fakultas',
                'pengajuan.kategori',
                'pengajuan.mahasiswa.prodi.fakultas',
                'pengajuan.mahasiswa.kerjaPraktek',
                'pengajuan.mahasiswa.tugasAkhir',
                'pengajuan.mahasiswa.sertifikasi',
                'pengajuan.mahasiswa.cplSkors.cpl',
                'pengajuan.mahasiswa.cplSkors.isiCapaian',
            ])->findOrFail($id);

            $cplData = $pengesahan->pengajuan->mahasiswa->cplSkors->map(function ($cplSkor) {
                return [
                    'id_cpl' => $cplSkor->cpl->id_cpl,
                    'nama_cpl' => $cplSkor->cpl->nama_cpl,
                    'skor_cpl' => $cplSkor->skor_cpl,
                    'isi_capaian' => collect($cplSkor->isiCapaian)->map(function ($isi) {
                        return [
                            'id_capaian' => $isi->id_capaian,
                            'deskripsi_indo' => $isi->deskripsi_indo,
                            'deskripsi_inggris' => $isi->deskripsi_inggris,
                        ];
                    })
                ];
            });

            return response()->json([
                'message' => 'Data pengesahan detail berhasil diambil',
                'data' => $pengesahan,
                'cpl_data' => $cplData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data pengesahan detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

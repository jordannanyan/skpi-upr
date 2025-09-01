<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller as BaseController;

class MahasiswaController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = Mahasiswa::with('prodi');

            if ($request->has('id_prodi')) {
                $query->where('id_prodi', $request->id_prodi);
            }

            if ($request->has('id_fakultas')) {
                $query->whereHas('prodi.fakultas', function ($q) use ($request) {
                    $q->where('id_fakultas', $request->id_fakultas);
                });
            }

            $data = $query->get();

            return response()->json([
                'message' => 'Mahasiswa fetched successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch mahasiswa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Storing mahasiswa:', $request->all());
            $validated = $request->validate([
                'id_prodi'        => 'required|integer|exists:tb_prodi,id_prodi',
                'nama_mahasiswa'  => 'required|string',
                'username'        => 'required|string|unique:tb_mahasiswa,username',
                'password'        => 'required|string',
                'tgl_masuk'       => 'required|date',
                'tgl_keluar'      => 'nullable|date',
                'no_telp'         => 'nullable|string',
                'alamat'          => 'nullable|string',
                'tanggal_lahir'   => 'required|date',
                'tempat_lahir'    => 'required|string',
                'nim_mahasiswa'   => 'required|string|unique:tb_mahasiswa,nim_mahasiswa',
            ]);

            $validated['password'] = bcrypt($validated['password']);
            $mahasiswa = Mahasiswa::create($validated);

            return response()->json(['message' => 'Mahasiswa created successfully', 'data' => $mahasiswa], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to store mahasiswa', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $mahasiswa = Mahasiswa::findOrFail($id);
            return response()->json(['message' => 'Mahasiswa fetched successfully', 'data' => $mahasiswa], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Mahasiswa not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch mahasiswa', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $mahasiswa = Mahasiswa::findOrFail($id);
            Log::info('Updating mahasiswa:', $request->all());

            $validated = $request->validate([
                'id_prodi'        => 'sometimes|integer|exists:tb_prodi,id_prodi',
                'nama_mahasiswa'  => 'sometimes|string',
                'username'        => 'sometimes|string|unique:tb_mahasiswa,username,' . $id . ',id_mahasiswa',
                'password'        => 'sometimes|string',
                'tgl_keluar'      => 'sometimes|date|nullable',
                'tgl_masuk'       => 'sometimes|date',
                'no_telp'         => 'sometimes|string',
                'alamat'          => 'sometimes|string',
                'tanggal_lahir'   => 'sometimes|date',
                'tempat_lahir'    => 'sometimes|string',
                'nim_mahasiswa'   => 'sometimes|string|unique:tb_mahasiswa,nim_mahasiswa,' . $id . ',id_mahasiswa',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $mahasiswa->update($validated);

            return response()->json(['message' => 'Mahasiswa updated successfully', 'data' => $mahasiswa], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Mahasiswa not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update mahasiswa', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $mahasiswa = Mahasiswa::findOrFail($id);
            $mahasiswa->delete();
            return response()->json(['message' => 'Mahasiswa deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Mahasiswa not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete mahasiswa', 'error' => $e->getMessage()], 500);
        }
    }


    // ===== NEW / REPLACE =====
    public function template()
    {
        $headers = [
            'nim_mahasiswa',
            'nama_mahasiswa',
            'username',
            'id_prodi',
            'tgl_masuk',       // YYYY-MM-DD
            'tempat_lahir',
            'tanggal_lahir',   // YYYY-MM-DD
            'no_telp',
            'alamat',
            'password'         // opsional, jika kosong default = nim_mahasiswa untuk record baru
        ];

        $csv = implode(',', $headers) . "\n";
        // contoh baris (optional):
        // $csv .= "2103110001,Jordan Nanyan,jordan,5,2023-08-01,Palangka Raya,2000-01-15,08123456789,Jl. Garuda,2103110001\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_mahasiswa.csv"',
        ]);
    }

    // ===== NEW / REPLACE =====
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048', // CSV/TXT only, 2MB
        ], [
            'file.mimes' => 'Format file harus CSV (.csv) atau teks (.txt).',
            'file.max'   => 'Ukuran file maksimal 2 MB.',
        ]);

        $file = $request->file('file');

        try {
            $rows = $this->readCsv($file->getRealPath());
            if (count($rows) < 2) {
                return response()->json(['message' => 'File tidak memiliki data'], 422);
            }

            // header
            $header = array_map(function ($h) {
                $h = trim($h);
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h); // hapus BOM jika ada
                return strtolower($h);
            }, $rows[0]);

            $required = ['nim_mahasiswa', 'nama_mahasiswa', 'username', 'id_prodi', 'tgl_masuk', 'tempat_lahir', 'tanggal_lahir'];
            foreach ($required as $col) {
                if (!in_array($col, $header, true)) {
                    return response()->json(['message' => "Kolom $col tidak ditemukan di header"], 422);
                }
            }
            $idx = array_flip($header);

            $created = 0;
            $updated = 0;
            $errors  = [];

            // proses baris data
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if ($this->rowEmpty($row)) continue;

                $nim     = trim($row[$idx['nim_mahasiswa']] ?? '');
                $nama    = trim($row[$idx['nama_mahasiswa']] ?? '');
                $user    = trim($row[$idx['username']] ?? '');
                $prodiId = (int)($row[$idx['id_prodi']] ?? 0);
                $tglMasuk = trim($row[$idx['tgl_masuk']] ?? '');
                $tplLhr  = trim($row[$idx['tempat_lahir']] ?? '');
                $tglLhr  = trim($row[$idx['tanggal_lahir']] ?? '');
                $telp    = isset($idx['no_telp']) ? trim($row[$idx['no_telp']]) : null;
                $alamat  = isset($idx['alamat']) ? trim($row[$idx['alamat']]) : null;
                $pwdRaw  = isset($idx['password']) ? (string)$row[$idx['password']] : '';

                // validasi ringan per baris
                $v = Validator::make([
                    'nim_mahasiswa'  => $nim,
                    'nama_mahasiswa' => $nama,
                    'username'       => $user,
                    'id_prodi'       => $prodiId,
                    'tgl_masuk'      => $tglMasuk,
                    'tempat_lahir'   => $tplLhr,
                    'tanggal_lahir'  => $tglLhr,
                    'no_telp'        => $telp,
                    'alamat'         => $alamat,
                ], [
                    'nim_mahasiswa'  => 'required|string|max:50',
                    'nama_mahasiswa' => 'required|string|max:255',
                    'username'       => 'required|string|max:100',
                    'id_prodi'       => 'required|integer|exists:tb_prodi,id_prodi',
                    'tgl_masuk'      => 'required|date',
                    'tempat_lahir'   => 'required|string|max:100',
                    'tanggal_lahir'  => 'required|date',
                    'no_telp'        => 'nullable|string|max:20',
                    'alamat'         => 'nullable|string|max:255',
                ]);

                if ($v->fails()) {
                    $errors[] = ['row' => $i + 1, 'nim' => $nim, 'errors' => $v->errors()->all()];
                    continue;
                }

                DB::beginTransaction();
                try {
                    $existing = \App\Models\Mahasiswa::where('nim_mahasiswa', $nim)->first();

                    // Cek unik username (kecuali dirinya sendiri saat update)
                    $usernameTaken = \App\Models\Mahasiswa::where('username', $user)
                        ->when($existing, fn($q) => $q->where('id_mahasiswa', '!=', $existing->id_mahasiswa))
                        ->exists();

                    if ($usernameTaken) {
                        $errors[] = ['row' => $i + 1, 'nim' => $nim, 'errors' => ['Username sudah dipakai mahasiswa lain']];
                        DB::rollBack();
                        continue;
                    }

                    $data = [
                        'id_prodi'        => $prodiId,
                        'nama_mahasiswa'  => $nama,
                        'username'        => $user,
                        'tgl_masuk'       => $tglMasuk,
                        'tempat_lahir'    => $tplLhr,
                        'tanggal_lahir'   => $tglLhr,
                        'no_telp'         => $telp,
                        'alamat'          => $alamat,
                        'nim_mahasiswa'   => $nim,
                    ];

                    // password: jika kosong, default = nim untuk record baru
                    if ($pwdRaw !== '') {
                        $data['password'] = bcrypt($pwdRaw);
                    } elseif (!$existing) {
                        $data['password'] = bcrypt($nim);
                    }

                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                    } else {
                        \App\Models\Mahasiswa::create($data);
                        $created++;
                    }

                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $errors[] = ['row' => $i + 1, 'nim' => $nim, 'errors' => [$e->getMessage()]];
                }
            }

            return response()->json([
                'message' => 'Import processed',
                'created' => $created,
                'updated' => $updated,
                'errors'  => $errors,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to import mahasiswa', 'error' => $e->getMessage()], 500);
        }
    }

    // ===== helpers =====
    private function readCsv(string $path): array
    {
        $rows = [];
        $fh = fopen($path, 'r');
        if ($fh === false) return $rows;
        while (($data = fgetcsv($fh)) !== false) {
            $rows[] = $data; // fgetcsv sudah handle tanda kutip dan koma
        }
        fclose($fh);
        return $rows;
    }

    private function rowEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string)$cell) !== '') return false;
        }
        return true;
    }
}

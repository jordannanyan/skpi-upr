<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaStoreRequest;
use App\Http\Requests\TaUpdateRequest;
use App\Models\TugasAkhir;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TugasAkhirController extends Controller
{
    /**
     * GET /api/ta?nim=&prodi_id=&fakultas_id=&q=&sort=&dir=&per_page=
     */
    public function index(Request $req)
    {
        $perPage = (int) ($req->integer('per_page') ?: 25);
        $user = $req->user();

        $sort = (string) ($req->string('sort') ?: 'id');
        $dir  = strtolower((string) $req->string('dir')) === 'desc' ? 'desc' : 'asc';
        $allowedSort = ['id','nim','kategori','judul','created_at','nama_mhs','nama_prodi','nama_fakultas'];
        if (!in_array($sort, $allowedSort, true)) $sort = 'id';

        $q = TugasAkhir::query()
            ->with([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ])
            ->ofNim($req->string('nim'))
            ->ofProdi($req->integer('prodi_id'))
            ->ofFakultas($req->integer('fakultas_id'))
            ->search($req->string('q'));

        // Paksa scope sesuai role user (abaikan manipulasi filter client)
        $this->applyScope($q, $user);

        // Sorting (nama_* via subquery)
        if (in_array($sort, ['nama_mhs','nama_prodi','nama_fakultas'], true)) {
            $q->sort($sort, $dir);
        } else {
            $q->orderBy($sort, $dir);
        }

        $rows = $q->paginate($perPage)->appends($req->query());
        return response()->json($rows);
    }

    /**
     * GET /api/ta/{id}
     */
    public function show(int $id, Request $req)
    {
        $user = $req->user();

        $row = TugasAkhir::with([
            'mahasiswa:nim,nama_mahasiswa,id_prodi',
            'mahasiswa.prodi:id,nama_prodi,id_fakultas',
            'mahasiswa.prodi.fakultas:id,nama_fakultas',
        ])->findOrFail($id);

        $this->assertRowInScope($row, $user);

        return response()->json($row);
    }

    /**
     * ➕ GET /api/mahasiswa/{nim}/tugas-akhir
     * Shortcut by NIM untuk halaman detail SKPI.
     */
    public function indexByMahasiswa(string $nim, Request $req)
    {
        $q = TugasAkhir::query()
            ->with([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ])
            ->ofNim($nim);

        // Don't apply scope here - access control should be at the laporan level
        // If user can view the laporan, they should see all TA data for that mahasiswa

        $rows = $q->orderBy('id', 'desc')->get();
        return response()->json($rows);
    }

    /**
     * POST /api/ta
     */
    public function store(TaStoreRequest $req)
    {
        $user = $req->user();
        $data = $req->validated();

        // Pastikan NIM berada dalam scope user
        $this->assertNimInScope($data['nim'], $user);

        $row = TugasAkhir::create($data);

        return response()->json(
            $row->load([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ]),
            201
        );
    }

    /**
     * PUT/PATCH /api/ta/{id}
     */
    public function update(TaUpdateRequest $req, int $id)
    {
        $user = $req->user();
        $row = TugasAkhir::findOrFail($id);

        // Pastikan row dalam scope user
        $this->assertRowInScope($row, $user);

        $data = $req->validated();

        // Jika NIM berubah, validasi scope NIM baru
        if (array_key_exists('nim', $data) && $data['nim'] !== $row->nim) {
            $this->assertNimInScope($data['nim'], $user);
        }

        $row->update($data);

        return response()->json(
            $row->load([
                'mahasiswa:nim,nama_mahasiswa,id_prodi',
                'mahasiswa.prodi:id,nama_prodi,id_fakultas',
                'mahasiswa.prodi.fakultas:id,nama_fakultas',
            ])
        );
    }

    /**
     * DELETE /api/ta/{id}
     */
    public function destroy(int $id, Request $req)
    {
        $user = $req->user();
        $row = TugasAkhir::findOrFail($id);

        // Pastikan row dalam scope user
        $this->assertRowInScope($row, $user);

        $row->delete();
        return response()->json(['deleted' => true]);
    }

    /* =========================
       Helpers: Scope enforcement
       ========================= */

    /**
     * Terapkan scope ke query berdasarkan role user.
     */
    private function applyScope(Builder $q, $user): void
    {
        if ($user->isProdiScoped()) {
            $q->whereHas('mahasiswa', fn($mq) => $mq->where('id_prodi', $user->id_prodi));
        } elseif ($user->isFacultyScoped()) {
            $q->whereHas('mahasiswa.prodi', fn($p) => $p->where('id_fakultas', $user->id_fakultas));
        }
    }

    /**
     * Pastikan sebuah row berada dalam scope user.
     */
    private function assertRowInScope(TugasAkhir $row, $user): void
    {
        if ($user->isProdiScoped()) {
            $idp = DB::table('ref_mahasiswa')->where('nim', $row->nim)->value('id_prodi');
            abort_unless($idp && (int)$idp === (int)$user->id_prodi, 403, 'Out of prodi scope');
        } elseif ($user->isFacultyScoped()) {
            $idf = DB::table('ref_mahasiswa as m')
                ->join('ref_prodi as p', 'p.id', '=', 'm.id_prodi')
                ->where('m.nim', $row->nim)
                ->value('p.id_fakultas');
            abort_unless($idf && (int)$idf === (int)$user->id_fakultas, 403, 'Out of faculty scope');
        }
    }

    /**
     * Pastikan NIM berada dalam scope user (untuk store/update).
     */
    private function assertNimInScope(string $nim, $user): void
    {
        if ($user->isProdiScoped()) {
            $idp = DB::table('ref_mahasiswa')->where('nim', $nim)->value('id_prodi');
            abort_unless($idp && (int)$idp === (int)$user->id_prodi, 403, 'Out of prodi scope');
        } elseif ($user->isFacultyScoped()) {
            $idf = DB::table('ref_mahasiswa as m')
                ->join('ref_prodi as p', 'p.id', '=', 'm.id_prodi')
                ->where('m.nim', $nim)
                ->value('p.id_fakultas');
            abort_unless($idf && (int)$idf === (int)$user->id_fakultas, 403, 'Out of faculty scope');
        }
    }
}

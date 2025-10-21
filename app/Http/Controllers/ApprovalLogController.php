<?php

namespace App\Http\Controllers;

use App\Models\ApprovalLog;
use Illuminate\Http\Request;

class ApprovalLogController extends Controller
{
    // GET /api/approval-logs?laporan_id=&nim=&actor_id=&role=&action=&level=&date_from=&date_to=&per_page=
    public function index(Request $req)
    {
        $per = (int) ($req->integer('per_page') ?: 25);

        $q = ApprovalLog::query()
            ->with([
                'actor:id,username,role',
                'laporan:id,nim,status',  // cukup kolom ringkas
            ])
            ->when($req->filled('laporan_id'), fn($x)=>$x->where('laporan_id', (int)$req->integer('laporan_id')))
            ->when($req->filled('actor_id'),   fn($x)=>$x->where('actor_id', (int)$req->integer('actor_id')))
            ->when($req->filled('role'),       fn($x)=>$x->where('actor_role', $req->string('role')))
            ->when($req->filled('action'),     fn($x)=>$x->where('action', $req->string('action')))
            ->when($req->filled('level'),      fn($x)=>$x->where('level',  (int)$req->integer('level')))
            // filter by NIM via relation
            ->when($req->filled('nim'), function($x) use ($req){
                $x->whereHas('laporan', fn($l)=>$l->where('nim', $req->string('nim')));
            })
            // date range (created_at)
            ->when($req->filled('date_from'), fn($x)=>$x->whereDate('created_at','>=',$req->date('date_from')))
            ->when($req->filled('date_to'),   fn($x)=>$x->whereDate('created_at','<=',$req->date('date_to')))
            ->latest('id');

        return response()->json($q->paginate($per)->appends($req->query()));
    }

    // GET /api/approval-logs/{id}
    public function show(int $id)
    {
        $row = ApprovalLog::with([
            'actor:id,username,role',
            'laporan:id,nim,status',
        ])->findOrFail($id);

        return response()->json($row);
    }
}

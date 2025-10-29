<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalLog extends Model
{
    protected $table = 'approval_logs';

    // Standarisasi action & level (sesuai ENUM di database)
    public const ACT_CREATE          = 'CREATE';
    public const ACT_SUBMIT          = 'SUBMIT';
    public const ACT_VERIFY          = 'VERIFY';
    public const ACT_APPROVE_WAKADEK = 'APPROVE_WAKADEK';
    public const ACT_APPROVE_DEKAN   = 'APPROVE_DEKAN';
    public const ACT_REJECT          = 'REJECT';
    public const ACT_SET_PENGESAHAN  = 'SET_PENGESAHAN';

    // 0=submission (AdminJurusan), 1=wakadek, 2=dekan
    public const LVL_SUBMISSION = 0;
    public const LVL_WAKADEK    = 1;
    public const LVL_DEKAN      = 2;

    protected $fillable = [
        'laporan_id',
        'actor_id',
        'actor_role',
        'action',
        'level',
        'note',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanSkpi::class, 'laporan_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

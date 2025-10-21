<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalLog extends Model
{
    protected $table = 'approval_logs';

    // Standarisasi action & level
    public const ACT_SUBMITTED   = 'submitted';
    public const ACT_VERIFIED    = 'verified';
    public const ACT_APPROVED    = 'approved';
    public const ACT_REJECTED    = 'rejected';
    public const ACT_REGENERATED = 'regenerated';

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

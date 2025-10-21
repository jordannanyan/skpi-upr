<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MahasiswaIndexRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'prodi_id'     => ['nullable','integer','min:1'],
            'fakultas_id'  => ['nullable','integer','min:1'],
            'q'            => ['nullable','string','max:100'],
            'sort'         => ['nullable','string','in:nim,nama_mahasiswa,tgl_masuk,tgl_yudisium,id_prodi,created_at'],
            'dir'          => ['nullable','string','in:asc,desc'],
            'per_page'     => ['nullable','integer','min:1','max:200'],
        ];
    }
}

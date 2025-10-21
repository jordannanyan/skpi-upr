<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProdiIndexRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'fakultas_id' => ['nullable','integer','min:1'],
            'q'           => ['nullable','string','max:100'],
            'sort'        => ['nullable','string','in:id,nama_prodi,nama_singkat,jenis_jenjang,id_fakultas,created_at'],
            'dir'         => ['nullable','string','in:asc,desc'],
            'per_page'    => ['nullable','integer','min:1','max:200'],
            'with_counts' => ['nullable','boolean'],
        ];
    }
}

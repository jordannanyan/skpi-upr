<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FakultasIndexRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'q'           => ['nullable','string','max:100'],
            'sort'        => ['nullable','string','in:id,nama_fakultas,nama_dekan,nip,created_at'],
            'dir'         => ['nullable','string','in:asc,desc'],
            'per_page'    => ['nullable','integer','min:1','max:200'],
            'with_counts' => ['nullable','boolean'],   // hitung prodi & mhs
            'with'        => ['nullable','string'],    // e.g. with=prodi
        ];
    }
}

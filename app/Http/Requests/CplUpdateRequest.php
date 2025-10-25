<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CplUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // PK kode_cpl tidak diubah via update (lebih aman)
        return [
            'id_prodi' => ['sometimes','nullable','integer','exists:ref_prodi,id'],
            'kategori' => ['sometimes','string','max:50'],
            'deskripsi'=> ['sometimes','nullable','string'],
        ];
    }
}

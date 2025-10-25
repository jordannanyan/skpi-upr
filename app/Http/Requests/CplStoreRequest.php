<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CplStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'kode_cpl' => ['required','string','max:20','unique:cpl,kode_cpl'],
            'id_prodi' => ['nullable','integer','exists:ref_prodi,id'],
            'kategori' => ['required','string','max:50'],
            'deskripsi'=> ['nullable','string'], // kolom TEXT; batasi panjang opsional jika ingin
        ];
    }
}
